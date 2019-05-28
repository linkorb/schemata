<?php

namespace LinkORB\Schema\Service;

class GraphQLGeneratorService extends AbstractGeneratorService
{
    private const TYPE_INT     = 'Int';
    private const TYPE_FLOAT   = 'Float';
    private const TYPE_STRING  = 'String';
    private const TYPE_BOOLEAN = 'Boolean';
    private const TYPE_ID      = 'ID';

    protected const SCHEMA_EXT = 'graphql';

    public function generate(bool $bundle = false): void
    {
        $this->checkSchema();

        $this->checkDirectory();

        $this->deleteObsoleteFiles($bundle);

        $data = $this->mapSchema();

        if ($bundle) {
            $this->generateBundle($data);
        } else {
            $this->generateFiles($data);
        }
    }

    private function generateFiles($data): void
    {
        foreach ($data['types'] as $tableName => $table) {
            $output = "type $tableName {\n";
            foreach ($table['fields'] as $fieldName => $field) {
                $output .= str_repeat(' ', self::INDENT) . $fieldName . ": {$field['type']}\n";
            }

            if (isset($table['references'])) {
                foreach ($table['references'] as $referenceName => $reference) {
                    $output .= str_repeat(' ', self::INDENT) . $referenceName . ": {$reference['remote']}\n";
                }
            }

            $output .= "}\n";

            file_put_contents($this->pathOutput . '/' . $tableName . '.' . self::SCHEMA_EXT, $output);
        }
    }

    private function generateBundle($data): void
    {
        $output = '';
        foreach ($data['types'] as $tableName => $table) {
            $output .= "type $tableName {\n";

            foreach ($table['fields'] as $fieldName => $field) {
                $output .= str_repeat(' ', self::INDENT) . $fieldName . ": {$field['type']}\n";
            }

            if (isset($table['references'])) {
                foreach ($table['references'] as $referenceName => $reference) {
                    $output .= str_repeat(' ', self::INDENT) . $referenceName . ": {$reference['remote']}\n";
                }
            }

            $output .= "}\n\n";
        }

        $output = mb_substr($output, 0, -1);

        file_put_contents($this->pathOutput . '/' . self::BUNDLE_FILE . '.' . self::SCHEMA_EXT, $output);
    }

    private function mapSchema(): array
    {
        $map = [
            'tables' => [],
        ];

        foreach ($this->schema->getTables() as $key => $item) {
            $map['types'][$key] = [
                'fields'     => [],
                'references' => [],
            ];

            foreach ($item->getColumns() as $columnName => $column) {
                if (null !== $column->getForeignKey()) {
                    $map['types'][$key]['references'][$columnName . '_ref'] = [
                        'local'   => $column->getName(),
                        'remote'  => $column->getForeignKey(),
                        'reverse' => $column->getForeignTable() . '_reverse',
                    ];
                } else if (null !== $column->getCodelist()) {
                    $map['types'][$key]['references'][$columnName . '_ref'] = [
                        'local'   => $column->getName(),
                        'remote'  => 'codelist__' . $column->getCodelist() . '.code',
                        'reverse' => $column->getForeignTable() . '_reverse',
                    ];
                } else {
                    if ('id' === $column->getName()) {
                        $column->setType('id');
                    }
                    $map['types'][$key]['fields'][$columnName] = [
                        'description' => $column->getLabel() ?? strtoupper($column->getName()),
                        'type'        => $this->normalizeType($column->getType()),
                    ];

                    if (true === $column->isUnique()) {
                        $map['types'][$key]['fields'][$columnName]['unique'] = true;
                    }
                }
            }

            if (0 === count($map['types'][$key]['references'])) {
                unset($map['types'][$key]['references']);
            }
        }

        return $map;
    }

    protected function getType(): array
    {
        return [
            'double' => self::TYPE_FLOAT,

            'date'      => self::TYPE_STRING,
            'price'     => self::TYPE_STRING,
            'datetime'  => self::TYPE_STRING,
            'password'  => self::TYPE_STRING,
            'uuid'      => self::TYPE_STRING,
            'email'     => self::TYPE_STRING,
            'mobile'    => self::TYPE_STRING,
            'phone'     => self::TYPE_STRING,
            'fax'       => self::TYPE_STRING,
            'code'      => self::TYPE_STRING,
            'weight'    => self::TYPE_STRING,
            'money'     => self::TYPE_STRING,
            'blob'      => self::TYPE_STRING,
            'null'      => self::TYPE_STRING,
            'color'     => self::TYPE_STRING,
            'text'      => self::TYPE_STRING,
            'string'    => self::TYPE_STRING,
            'usergroup' => self::TYPE_STRING,
            'varchar'   => self::TYPE_STRING,
            'enum'      => self::TYPE_STRING,
            null        => self::TYPE_STRING,

            'boolean' => self::TYPE_BOOLEAN,
            'bool'    => self::TYPE_BOOLEAN,

            'stamp' => self::TYPE_INT,
            'int'   => self::TYPE_INT,

            'id' => self::TYPE_ID,
        ];
    }
}
