<?php

namespace LinkORB\Schemata\Service;

use Symfony\Component\Yaml\Yaml;

class YamlGeneratorService extends AbstractGeneratorService
{
    private const TYPE_INTEGER = 'integer';
    private const TYPE_STRING  = 'string';

    protected const SCHEMA_EXT = 'yaml';

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
        foreach ($data['tables'] as $tableName => $table) {
            $dump = Yaml::dump($table, 6, self::INDENT);
            $dump = str_replace("'", '', $dump);

            file_put_contents($this->pathOutput . '/' . $tableName . '.' . self::SCHEMA_EXT, "---\n" . $dump);
        }
    }

    private function generateBundle($data): void
    {
        $dump = Yaml::dump($data, 6, self::INDENT);
        $dump = str_replace("'", '', $dump);

        file_put_contents($this->pathOutput . '/' . self::BUNDLE_FILE . '.' . self::SCHEMA_EXT, "---\n" . $dump);
    }

    private function mapSchema(): array
    {
        $map = [
            'tables' => [],
        ];

        foreach ($this->schema->getTables() as $key => $item) {
            $map['tables'][$key] = [
                'columns'    => [],
                'references' => [],
            ];

            foreach ($item->getColumns() as $columnName => $column) {
                if (null !== $column->getForeignKey()) {
                    $map['tables'][$key]['references'][$columnName . '_ref'] = [
                        'local'   => $column->getName(),
                        'remote'  => $column->getForeignKey(),
                        'reverse' => $column->getForeignTable() . '_reverse',
                    ];
                } else if (null !== $column->getCodelist()) {
                    $map['tables'][$key]['references'][$columnName . '_ref'] = [
                        'local'   => $column->getName(),
                        'remote'  => 'codelist__' . $column->getCodelist() . '.code',
                        'reverse' => $column->getForeignTable() . '_reverse',
                    ];
                } else {
                    $map['tables'][$key]['columns'][$columnName] = [
                        'description' => $column->getLabel() ?? strtoupper($column->getName()),
                        'type'        => $this->normalizeType($column->getType()),
                    ];

                    if (true === $column->isUnique()) {
                        $map['tables'][$key]['columns'][$columnName]['unique'] = true;
                    }
                }
            }

            if (0 === count($map['tables'][$key]['references'])) {
                unset($map['tables'][$key]['references']);
            }
        }

        return $map;
    }

    protected function getType(): array
    {
        return [
            'double'    => self::TYPE_STRING,
            'date'      => self::TYPE_STRING,
            'price'     => self::TYPE_STRING,
            'datetime'  => self::TYPE_STRING,
            'password'  => self::TYPE_STRING,
            'boolean'   => self::TYPE_STRING,
            'uuid'      => self::TYPE_STRING,
            'bool'      => self::TYPE_STRING,
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

            'stamp' => self::TYPE_INTEGER,
            'int'   => self::TYPE_INTEGER,
        ];
    }
}
