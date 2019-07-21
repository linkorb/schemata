<?php

namespace LinkORB\Schemata\Service;

use LinkORB\Schemata\Entity\Codelist;
use LinkORB\Schemata\Entity\Field;
use LinkORB\Schemata\Entity\Issue;
use LinkORB\Schemata\Entity\Note;
use LinkORB\Schemata\Entity\Type;
use LinkORB\Schemata\Entity\Tag;
use LinkORB\Schemata\Entity\XmlPackage;
use LinkORB\Schemata\Entity\Schema;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class SchemaService
{
    public const CODELISTS_AS_TABLES = 1;

    private const CONFIG_FILE = 'schemata.yml';

    /** @var array */
    private $typesArray = [];

    /** @var string */
    private $pathSchema;

    /** @var null | int */
    private $flag;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var string[]
     */
    private $aliasWhitelist;

    public function __construct($pathSchema, $flag = null)
    {
        $this->pathSchema = $pathSchema;
        $this->flag = $flag;

        $this->validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        $this->aliasWhitelist = $this->getAliasWhitelistFromConfig($pathSchema);
    }

    public function parseSchema(): void
    {
        $this->schema = new Schema();

        $this->parseXml();
        $this->parseCsv();
    }

    private function parseXml(): void
    {
        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new XmlEncoder()]
        );

        $finder = new Finder();
        $finder
            ->files()
            ->in($this->pathSchema)
            ->name(['*.xml']);

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $package = $serializer->deserialize(
                $contents,
                XmlPackage::class,
                XmlEncoder::FORMAT
            );
            $this->addTypes($package->getTables());
        }

        $this->hydrateTypes();
    }

    private function parseCsv(): void
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->pathSchema)
            ->name(['*.csv']);

        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new CsvEncoder()]
        );

        foreach ($finder as $file) {
            $contents = $this->cleanUpCsvFile($file->getContents());

            $context = [
                CsvEncoder::DELIMITER_KEY => ';',
            ];

            $codes = $serializer->decode($contents, CsvEncoder::FORMAT, $context);

            $codelist = new Codelist();
            $codelist->setName($file->getBasename('.csv'));
            $codelist->setItems($codes);

            if (self::CODELISTS_AS_TABLES === $this->flag) {
                $type = $this->convertCodelistToType($codelist);
                $this->schema->addCodelistAsType($type);
            } else {
                $this->schema->addCodelist($codelist);
            }
        }
    }

    private function addTypes(array $types): void
    {
        $this->typesArray = array_merge($this->typesArray, $types);
    }

    /**
     * @return Schema
     * @throws RuntimeException
     */
    public function getSchema(): Schema
    {
        if (!$this->schema instanceof Schema) {
            throw new RuntimeException('There is no Schema.');
        }

        return $this->schema;
    }

    private function hydrateTypes(): void
    {
        foreach ($this->typesArray as $item) {
            $typeName = $item['@name'];

            if (!isset($item['field'])) {
                $item['field'] = [];
            } else if (!isset($item['field'][0])) {
                $item['field'] = [$item['field']];
            }

            if (isset($item['@extended']) && 'true' === $item['@extended']) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $item['field'] = array_merge($item['field'], $this->getExtendedFields());
            }

            if (!array_key_exists($typeName, $this->schema->getTypes())) {
                $type = new Type();
                $type->setName($typeName);

                if (isset($item['@alias'])) {
                    $type->setAlias($item['@alias']);
                }

                $type->setProperties($this->getCustomProperties($item));

                // Add default fields
                $defaultFields = $this->prepareFields($this->getDefaultFields());
                $type->addFields($defaultFields['fields']);

                $errors = $this->validateType($type);

                if (
                    true === $defaultFields['hasErrors'] ||
                    0 < $errors->count()
                ) {
                    $this->schema->addTypeWithIssues($type);
                }

                $this->schema->setType($type);
            } else {
                $type = $this->schema->getType($typeName);
            }

            if (!empty ($item['column'])) {
                if (!isset($item['column'][0])) {
                    // the table element only contains one column
                    // then it should be cast to an array
                    $item['column'] = [$item['column']];
                }
                $res = $this->prepareFields($item['column']);
                $type->addFields($res['fields']);

                if (true === $res['hasErrors']) {
                    $this->schema->addTypeWithIssues($type);
                }
            }

            if (isset($item['@tags'])) {
                $tagNames = explode(',', $item['@tags']);
                foreach ($tagNames as $tagName) {
                    $tagName = trim($tagName);
                    if ($tagName) {
                        $tag = new Tag();
                        $tag->setName($tagName);
                        $type->addTag($tag);
                        if (!isset($this->schema->getTaggedTypes()[$tag->getName()][$typeName])) {
                            $this->schema->addTaggedType($tag, $type);
                        }
                    }
                }
            }

            if (!empty($item['issue']) && is_array($item['issue'])) {
                if (!isset($item['issue'][0])) {
                    $item['issue'] = [$item['issue']];
                }

                $issues = $this->prepareIssues($item['issue'], $type);

                $type->setIssues($issues);

                $this->schema->addTypeWithIssues($type);
            }
        }
    }

    /**
     * @param $fields
     * @return array
     */
    private function prepareFields(array $fields): array
    {
        $res = [
            'fields'   => [],
            'hasErrors' => false,
        ];

        foreach ($fields as $field) {
            if (!isset($field['@name'])) {
                // sanity check
                throw new RuntimeException("Invalid field element");
            }
            $name = $field['@name'];

            $newField = new Field();

            $newField->setName($name);
            $newField->setProperties($this->getCustomProperties($field));

            if (isset($field['@type'])) {
                $newField->setType($field['@type']);
            }

            if (isset($field['@label'])) {
                $newField->setLabel($field['@label']);
            }

            if (isset($field['@alias'])) {
                $newField->setAlias($field['@alias']);
            }
            if (isset($field['@generated'])) {
                $newField->setGenerated($field['@generated']);
            }

            if (isset($field['@doc'])) {
                $newField->setDoc($field['@doc']);
            }

            if (isset($field['@foreignkey'])) {
                $keys = explode('.', $field['@foreignkey']);
                if (2 === count($keys)) {
                    $newField->setForeignType($keys[0]);
                }
                $newField->setForeignKey($field['@foreignkey']);
            }

            if (isset($field['@codelist'])) {
                $newField->setCodelist($field['@codelist']);
                $newField->setType('codelist');
                $newField->setForeignType('codelist__' . $newField->getCodelist());
            }

            if (isset($field['@unique']) && is_bool($field['@unique'])) {
                $newField->setUnique($field['@unique']);
            }

            if (isset($field['@tags'])) {
                $tagNames = explode(',', $field['@tags']);
                foreach ($tagNames as $tagName) {
                    $tagName = trim($tagName);
                    if (!empty($tagName)) {
                        $tag = new Tag();
                        $tag->setName($tagName);
                        $newField->addTag($tag);
                    }
                }
            }

            if (!empty($field['issue']) && is_array($field['issue'])) {
                if (!isset($field['issue'][0])) {
                    $field['issue'] = [$field['issue']];
                }

                $issues = $this->prepareIssues($field['issue'], $newField);

                $newField->setIssues($issues);
            }

            $hasFieldAnyIssues = $this->makeFieldValidation($newField);

            if ($hasFieldAnyIssues) {
                $res['hasErrors'] = true;
            }

            $res['fields'][] = $newField;
        }

        return $res;
    }

    /**
     * @return array
     */
    private function getExtendedFields(): array
    {
        return [
            [
                '@name'      => 'r_uuid',
                '@type'      => 'varchar(40)',
                '@label'     => 'UUID',
                '@unique'    => true,
                '@alias'     => 'uniqueId',
                '@generated' => true,
            ],
            [
                '@name'      => 'r_c_s',
                '@type'      => 'int',
                '@label'     => 'Create time',
                '@alias'     => 'createdAt',
                '@generated' => true,
            ],
            [
                '@name'      => 'r_u_s',
                '@type'      => 'int',
                '@label'     => 'Update time',
                '@alias'     => 'updatedAt',
                '@generated' => true,
            ],
            [
                '@name'      => 'r_d_s',
                '@type'      => 'int',
                '@label'     => 'Delete time',
                '@alias'     => 'deletedAt',
                '@generated' => true,
            ],
            [
                '@name'      => 'r_c_u',
                '@type'      => 'varchar(40)',
                '@label'     => 'Creator UUID',
                '@alias'     => 'createdBy',
                '@generated' => true,
            ],
            [
                '@name'      => 'r_u_u',
                '@type'      => 'varchar(40)',
                '@label'     => 'Updater UUID',
                '@alias'     => 'updatedBy',
                '@generated' => true,
            ],
            [
                '@name'      => 'r_d_u',
                '@type'      => 'varchar(40)',
                '@label'     => 'Deleter UUID',
                '@alias'     => 'deletedBy',
                '@generated' => true,
            ],
        ];
    }

    private function getDefaultFields(): array
    {
        return [
            [
                '@name'      => 'id',
                '@alias'     => 'id',
                '@type'      => 'int',
                '@unique'    => true,
                '@generated' => true,
            ],
        ];
    }

    private function getCodelistFields(): array
    {
        return [
            [
                '@name'   => 'code',
                '@type'   => 'string',
                '@unique' => true,
            ],
            [
                '@name' => 'label',
                '@type' => 'string',
            ],
        ];
    }

    private function getCustomProperties($item): array
    {
        $properties = [];

        foreach ($item as $key => $value) {
            if (0 === strpos($key, '@p:')) {
                $property = str_replace('@p:', '', $key);
                $properties[$property] = $value;
            }
        }

        return $properties;
    }

    private function validateType(Type $type): ConstraintViolationList
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($type);

        if (0 < $errors->count()) {
            $iterator = $errors->getIterator();

            foreach ($iterator as $index => $violationItem) {
                if ($this->matchAliasWhitelist($violationItem)) {
                    $errors->remove($index);
                    continue;
                }

                $type->addViolation($violationItem);
            }
        }

        return $errors;
    }

    private function validateField(Field $field): ConstraintViolationList
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($field);

        if (0 < $errors->count()) {
            $iterator = $errors->getIterator();

            foreach ($iterator as $index => $violationItem) {
                if ($this->matchAliasWhitelist($violationItem)) {
                    $errors->remove($index);
                    continue;
                }

                $field->addViolation($violationItem);
            }
        }

        return $errors;
    }

    private function matchAliasWhitelist(ConstraintViolation $violationItem): bool
    {
        return (
            'alias' === $violationItem->getPropertyPath() &&
            in_array($violationItem->getInvalidValue(), $this->aliasWhitelist, true)
        );
    }

    private function convertCodelistToType(Codelist $codelist): Type
    {
        $name = 'codelist__' . $codelist->getName();
        $type = new Type();
        $type->setName($name);
        $fields = $this->prepareFields($this->getCodelistFields());
        $type->addFields($fields['fields']);

        if (true === $fields['hasErrors']) {
            $this->schema->addTypeWithIssues($type);
        }

        return $type;
    }

    private function cleanUpCsvFile($contents)
    {
        $contents = str_replace(";\n", "\n", $contents);
        $contents = preg_replace('/^#.*\n/', '', $contents);
        $contents = preg_replace('/(\n){2,}/', "\n", $contents);

        return $contents;
    }

    private function getAliasWhitelistFromConfig($path): array
    {
        try {
            $res = Yaml::parseFile($path . '/../' . self::CONFIG_FILE);
            if (!empty($res['alias-whitelist']) && is_array($res['alias-whitelist'])) {
                return $res['alias-whitelist'];
            }
        } catch (ParseException $e) {
            // do nothing
        }

        return [];
    }

    private function prepareIssues(array $issues, $parent): array
    {
        $newIssues = [];

        foreach ($issues as $idx => $issue) {
            $newIssue = new Issue($parent);

            if (!isset($issue['note'][0])) {
                $issues[$idx]['note'] = [$issue['note'] ?? null];
            }

            usort(
                $issues[$idx]['note'],
                static function ($a, $b) {
                    if ($a['@createdAt'] === $b['@createdAt']) {
                        return 0;
                    }

                    return ($a['@createdAt'] < $b['@createdAt']) ? -1 : 1;
                }
            );

            $issueNotes = [];

            foreach ($issues[$idx]['note'] as $idxNote => $note) {
                $newNote = new Note();

                if (!empty($note['@createdAt'])) {
                    $newNote->setCreatedAt($note['@createdAt']);
                }

                if (!empty($note['@author'])) {
                    $newNote->setAuthor($note['@author']);
                }

                if (!empty($note['#'])) {
                    $newNote->setMessage($note['#']);
                }

                $issueNotes[] = $newNote;
            }

            $newIssue->setNotes($issueNotes);

            if (!empty($issue['@status'])) {
                $newIssue->setStatus($issue['@status']);
            }

            if (!empty($issue['@type'])) {
                $newIssue->setType($issue['@type']);
            }

            $newIssues[] = $newIssue;
        }

        return $newIssues;
    }

    private function makeFieldValidation(Field $field): bool
    {
        $errors = $this->validateField($field);

        return (
            0 < $errors->count() ||
            0 < count($field->getIssues())
        );
    }
}
