<?php

namespace Schemata\Service;

use Schemata\Entity\Type;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class XMLGeneratorService extends AbstractGeneratorService
{
    protected const SCHEMA_EXT = 'xml';

    public function generate(): void
    {
        $this->checkDirectory();

        $this->deleteObsoleteFiles();

        $encoder = new XmlEncoder();

        foreach ($this->schema->getTypes() as $type) {
            $data = $this->prepareDataArray($type);

            $dump = $encoder->encode($data, XmlEncoder::FORMAT, [
                XmlEncoder::ROOT_NODE_NAME => 'root',
                XmlEncoder::STANDALONE     => false,
                XmlEncoder::FORMAT_OUTPUT  => true,
            ]);

            $filename = $type->getName();

            file_put_contents($this->pathOutput . '/' . $filename . '.' . static::SCHEMA_EXT, $dump);
        }
    }

    private function prepareDataArray(Type $type): array
    {
        $data = [
            'table' => [
                '@name'  => $type->getName(),
                'column' => [],
            ],
        ];

        foreach ($type->getColumns() as $column) {
            $data['table']['column'][] = [
                '@name' => $column->getName(),
                '@type' => $column->getType(),
            ];
        }

        return $data;
    }
}
