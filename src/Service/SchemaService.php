<?php

namespace LinkORB\Schemata\Service;

use LinkORB\Schemata\Entity\Codelist;
use LinkORB\Schemata\Entity\Column;
use LinkORB\Schemata\Entity\Table;
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
    private $tablesArray = [];

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
            $this->addTables($package->getTables());
        }

        $this->hydrateTables();
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
                $table = $this->convertCodelistToTable($codelist);
                $this->schema->addCodelistAsTable($table);
            } else {
                $this->schema->addCodelist($codelist);
            }
        }
    }

    private function addTables(array $tables): void
    {
        $this->tablesArray = array_merge($this->tablesArray, $tables);
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

    private function hydrateTables(): void
    {
        foreach ($this->tablesArray as $item) {
            $tableName = $item['@name'];

            if (!isset($item['column'])) {
                $item['column'] = [];
            } else if (!isset($item['column'][0])) {
                $item['column'] = [$item['column']];
            }

            if (isset($item['@extended']) && 'true' === $item['@extended']) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $item['column'] = array_merge($item['column'], $this->getExtendedColumns());
            }

            if (!array_key_exists($tableName, $this->schema->getTables())) {
                $table = new Table();
                $table->setName($tableName);

                if (isset($item['@alias'])) {
                    $table->setAlias($item['@alias']);
                }

                $table->setProperties($this->getCustomProperties($item));

                // Add default columns
                $defaultColumns = $this->prepareColumns($this->getDefaultColumns());
                $table->addColumns($defaultColumns['columns']);

                $errors = $this->validateTable($table);

                if (
                    true === $defaultColumns['hasErrors'] ||
                    0 < $errors->count()
                ) {
                    $this->schema->addTableWithIssues($table);
                }

                $this->schema->setTable($table);
            } else {
                $table = $this->schema->getTable($tableName);
            }

            if (!empty ($item['column'])) {
                $newColumns = $this->prepareColumns($item['column']);
                $table->addColumns($newColumns['columns']);

                if (true === $newColumns['hasErrors']) {
                    $this->schema->addTableWithIssues($table);
                }
            }

            if (isset($item['@tags'])) {
                $tagNames = explode(',', $item['@tags']);
                foreach ($tagNames as $tagName) {
                    $tagName = trim($tagName);
                    if ($tagName) {
                        $tag = new Tag();
                        $tag->setName($tagName);
                        $table->addTag($tag);
                        if (!isset($this->schema->getTaggedTables()[$tag->getName()][$tableName])) {
                            $this->schema->addTaggedTable($tag, $table);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $columns
     * @return array
     */
    private function prepareColumns($columns): array
    {
        $res = [
            'columns'   => [],
            'hasErrors' => false,
        ];

        foreach ($columns as $column) {
            $name = $column['@name'];

            $newColumn = new Column();

            $newColumn->setName($name);
            $newColumn->setProperties($this->getCustomProperties($column));

            if (isset($column['@type'])) {
                $newColumn->setType($column['@type']);
            }

            if (isset($column['@label'])) {
                $newColumn->setLabel($column['@label']);
            }

            if (isset($column['@alias'])) {
                $newColumn->setAlias($column['@alias']);
            }
            if (isset($column['@generated'])) {
                $newColumn->setGenerated($column['@generated']);
            }

            if (isset($column['@doc'])) {
                $newColumn->setDoc($column['@doc']);
            }

            if (isset($column['@foreignkey'])) {
                $keys = explode('.', $column['@foreignkey']);
                if (2 === count($keys)) {
                    $newColumn->setForeignTable($keys[0]);
                }
                $newColumn->setForeignKey($column['@foreignkey']);
            }

            if (isset($column['@codelist'])) {
                $newColumn->setCodelist($column['@codelist']);
                $newColumn->setType('codelist');
                $newColumn->setForeignTable('codelist__' . $newColumn->getCodelist());
            }

            if (isset($column['@unique']) && is_bool($column['@unique'])) {
                $newColumn->setUnique($column['@unique']);
            }

            if (isset($column['@tags'])) {
                $tagNames = explode(',', $column['@tags']);
                foreach ($tagNames as $tagName) {
                    $tagName = trim($tagName);
                    if (!empty($tagName)) {
                        $tag = new Tag();
                        $tag->setName($tagName);
                        $newColumn->addTag($tag);
                    }
                }
            }

            $errors = $this->validateColumn($newColumn);

            if (false === $res['hasErrors'] && 0 < $errors->count()) {
                $res['hasErrors'] = true;
            }

            $res['columns'][] = $newColumn;
        }

        return $res;
    }

    /**
     * @return array
     */
    private function getExtendedColumns(): array
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

    private function getDefaultColumns(): array
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

    private function getCodelistColumns(): array
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

    private function validateTable(Table $table): ConstraintViolationList
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($table);

        if (0 < $errors->count()) {
            $iterator = $errors->getIterator();

            foreach ($iterator as $index => $violationItem) {
                if ($this->matchAliasWhitelist($violationItem)) {
                    $errors->remove($index);
                    continue;
                }

                $table->addViolation($violationItem);
            }
        }

        return $errors;
    }

    private function validateColumn(Column $column): ConstraintViolationList
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($column);

        if (0 < $errors->count()) {
            $iterator = $errors->getIterator();

            foreach ($iterator as $index => $violationItem) {
                if ($this->matchAliasWhitelist($violationItem)) {
                    $errors->remove($index);
                    continue;
                }

                $column->addViolation($violationItem);
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

    private function convertCodelistToTable(Codelist $codelist): Table
    {
        $name = 'codelist__' . $codelist->getName();
        $table = new Table();
        $table->setName($name);
        $columns = $this->prepareColumns($this->getCodelistColumns());
        $table->addColumns($columns['columns']);

        if (true === $columns['hasErrors']) {
            $this->schema->addTableWithIssues($table);
        }

        return $table;
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
}
