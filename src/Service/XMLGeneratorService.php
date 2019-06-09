<?php

namespace LinkORB\Schemata\Service;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

class XMLGeneratorService extends AbstractGeneratorService
{
    protected const SCHEMA_EXT = 'xml';

    public function generate(): void
    {
        // TODO: Implement generate() method.
    }

    public function generateXML(array $tables): void
    {
        $this->checkDirectory();

        $this->deleteObsoleteFiles();

        $encoder = new XmlEncoder();

        foreach ($tables as $table) {
            $dump = $encoder->encode($table, XmlEncoder::FORMAT, [
                XmlEncoder::ROOT_NODE_NAME => 'root',
                XmlEncoder::STANDALONE     => false,
                XmlEncoder::FORMAT_OUTPUT  => true,
            ]);

            $filename = $table['table']['@name'];

            file_put_contents($this->pathOutput . '/' . $filename . '.' . static::SCHEMA_EXT, $dump);
        }
    }

    protected function deleteObsoleteFiles(bool $bundle = false): void
    {
        array_map('unlink', glob("$this->pathOutput/*.*"));
    }
}
