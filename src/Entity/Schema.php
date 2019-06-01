<?php

namespace LinkORB\Schemata\Entity;

use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Schema
{
    /**
     * @var Table[]
     */
    private $tables = [];

    /**
     * @var Codelist[]
     */
    private $codelists = [];

    /**
     * @var array
     */
    private $taggedTables = [];

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function getTaggedTables(): array
    {
        return $this->taggedTables;
    }

    public function getTagsAll(): array
    {
        return array_keys($this->taggedTables);
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function hydrateTables($tablesArray): void
    {
        foreach ($tablesArray as $item) {
            $tableName = $item['@name'];

            if (!isset($item['column'])) {
                $item['column'] = [];
            } else {
                if (!isset($item['column'][0])) {
                    $item['column'] = [$item['column']];
                }
            }

            if (isset($item['@extended']) && 'true' === $item['@extended']) {
                $item['column'] = array_merge($item['column'], $this->getExtendedColumns());
            }

            if (!array_key_exists($tableName, $this->tables)) {
                $table = new Table();
                $table->setName($tableName);

                if (isset($item['@alias'])) {
                    $table->setAlias($item['@alias']);
                }

                $table->setProperties($this->getCustomProperties($item));
                $this->tables[$tableName] = $table;

                // Add default columns
                $this->tables[$tableName]->addColumns($this->getDefaultColumns(), $this->validator);

                /** @var ConstraintViolationList $errors */
                $errors = $this->validator->validate($table);

                if (0 < $errors->count()) {
                    $iterator = $errors->getIterator();

                    foreach ($iterator as $violationItem) {
                        $table->addViolation($violationItem);
                    }
                }
            }

            if (!empty ($item['column'])) {
                $this->tables[$tableName]->addColumns($item['column'], $this->validator);
            }

            if (isset($item['@tags'])) {
                $tagNames = explode(',', $item['@tags']);
                foreach ($tagNames as $tagName) {
                    $tagName = trim($tagName);
                    if ($tagName) {
                        $tag = new Tag();
                        $tag->setName($tagName);
                        $this->tables[$tableName]->addTag($tag);

                        if (!isset($this->taggedTables[$tagName][$tableName])) {
                            $this->taggedTables[$tagName][$tableName] = $this->tables[$tableName];
                        }
                    }
                }
            }
        }
    }

    public function addCodelist(Codelist $codelist): void
    {
        if (array_key_exists($codelist->getName(), $this->codelists)) {
            throw new \RuntimeException('Codelist duplication: "' . $codelist->getName() . '"');
        }

        $this->codelists[$codelist->getName()] = $codelist;
    }

    public function addCodelistAsTable(Codelist $codelist): void
    {
        $name = 'codelist__' . $codelist->getName();

        if (array_key_exists($name, $this->tables)) {
            throw new \RuntimeException('Codelist duplication: ' . $name);
        }

        $table = new Table();
        $table->setName($name);
        $this->tables[$name] = $table;

        $columns = [
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

        $this->tables[$name]->addColumns($columns, $this->validator);
    }

    /**
     * @return Codelist[]
     */
    public function getCodelists(): array
    {
        return $this->codelists;
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
}
