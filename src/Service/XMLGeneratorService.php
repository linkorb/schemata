<?php

namespace LinkORB\Schemata\Service;

use LinkORB\Schemata\Entity\Table;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class XMLGeneratorService extends AbstractGeneratorService
{
    protected const SCHEMA_EXT = 'xml';

    public function generate(): void
    {
        $this->checkDirectory();

        $this->deleteObsoleteFiles();

        $encoder = new XmlEncoder();

        foreach ($this->schema->getTables() as $table) {
            $data = $this->prepareDataArray($table);

            $dump = $encoder->encode($data, XmlEncoder::FORMAT, [
                XmlEncoder::ROOT_NODE_NAME => 'root',
                XmlEncoder::STANDALONE     => false,
                XmlEncoder::FORMAT_OUTPUT  => true,
            ]);

            $filename = $table->getName();

            file_put_contents($this->pathOutput . '/' . $filename . '.' . static::SCHEMA_EXT, $dump);
        }
    }

    private function prepareDataArray(Table $table): array
    {
        $data = [
            'table' => [
                '@name'  => $table->getName(),
                'column' => [],
            ],
        ];

        foreach ($table->getColumns() as $column) {
            $data['table']['column'][] = [
                '@name' => $column->getName(),
                '@type' => $column->getType(),
            ];
        }

        return $data;
    }
}
