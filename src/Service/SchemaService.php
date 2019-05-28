<?php

namespace LinkORB\Schema\Service;

use LinkORB\Schema\Entity\Codelist;
use LinkORB\Schema\Entity\XmlPackage;
use LinkORB\Schema\Entity\Schema;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SchemaService
{
    public const CODELISTS_AS_TABLES = 1;

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

    public function __construct($pathSchema, $flag = null)
    {
        $this->pathSchema = $pathSchema;
        $this->flag = $flag;
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

        $this->schema->hydrateTables($this->tablesArray);
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
            $contents = $file->getContents();
            $contents = str_replace([";\n", "\n\n"], "\n", $contents);
            $contents = preg_replace('/^#.*\n/', '', $contents);
            $context = [
                CsvEncoder::DELIMITER_KEY => ';',
            ];

            $codes = $serializer->decode($contents, CsvEncoder::FORMAT, $context);

            $codelist = new Codelist();
            $codelist->setName($file->getBasename('.csv'));
            $codelist->setItems($codes);

            if (self::CODELISTS_AS_TABLES === $this->flag) {
                $this->schema->addCodelistAsTable($codelist);
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
     * @throws \Exception
     */
    public function getSchema(): Schema
    {
        if (!$this->schema instanceof Schema) {
            throw new \RuntimeException('There is no Schema.');
        }

        return $this->schema;
    }
}
