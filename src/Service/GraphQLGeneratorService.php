<?php

namespace Schemata\Service;

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

        $this->deleteObsoleteFilesBundled($bundle);

        $data = $this->mapSchema();

        if ($bundle) {
            $this->generateBundle($data);
        } else {
            $this->generateFiles($data);
        }
    }

    private function generateFiles($data): void
    {
        foreach ($data['types'] as $typeName => $type) {
            $output = "type $typeName {\n";
            foreach ($type['fields'] as $fieldName => $field) {
                $output .= str_repeat(' ', self::INDENT) . $fieldName . ": {$field['type']}\n";
            }

            if (isset($type['references'])) {
                foreach ($type['references'] as $referenceName => $reference) {
                    $output .= str_repeat(' ', self::INDENT) . $referenceName . ": {$reference['remote']}\n";
                }
            }

            $output .= "}\n";

            file_put_contents($this->pathOutput . '/' . $typeName . '.' . self::SCHEMA_EXT, $output);
        }
    }

    private function generateBundle($data): void
    {
        $output = '';
        foreach ($data['types'] as $typeName => $type) {
            $output .= "type $typeName {\n";

            foreach ($type['fields'] as $fieldName => $field) {
                $output .= str_repeat(' ', self::INDENT) . $fieldName . ": {$field['type']}\n";
            }

            if (isset($type['references'])) {
                foreach ($type['references'] as $referenceName => $reference) {
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
            'types' => [],
        ];

        foreach ($this->schema->getTypes() as $key => $item) {
            $map['types'][$key] = [
                'fields'     => [],
                'references' => [],
            ];

            foreach ($item->getFields() as $fieldName => $field) {
                if (null !== $field->getForeignKey()) {
                    $map['types'][$key]['references'][$fieldName . '_ref'] = [
                        'local'   => $field->getName(),
                        'remote'  => $field->getForeignKey(),
                        'reverse' => $field->getForeignType() . '_reverse',
                    ];
                } else if (null !== $field->getCodelist()) {
                    $map['types'][$key]['references'][$fieldName . '_ref'] = [
                        'local'   => $field->getName(),
                        'remote'  => 'codelist__' . $field->getCodelist() . '.code',
                        'reverse' => $field->getForeignType() . '_reverse',
                    ];
                } else {
                    if ('id' === $field->getName()) {
                        $field->setType('id');
                    }
                    $map['types'][$key]['fields'][$fieldName] = [
                        'description' => $field->getLabel() ?? strtoupper($field->getName()),
                        'type'        => $this->normalizeType($field->getType()),
                    ];

                    if (true === $field->isUnique()) {
                        $map['types'][$key]['fields'][$fieldName]['unique'] = true;
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
