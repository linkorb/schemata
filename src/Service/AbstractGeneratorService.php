<?php

namespace Schemata\Service;

use Schemata\Entity\Schema;
use RuntimeException;

abstract class AbstractGeneratorService
{
    protected const BUNDLE_FILE = 'schema';

    protected const INDENT = 2;

    /**
     * @var Schema
     */
    protected $schema;

    /** @var string */
    protected $pathOutput;

    public function __construct(Schema $schema, $pathOutput)
    {
        $this->schema = $schema;
        $this->pathOutput = $pathOutput;
    }

    abstract public function generate();

    protected function checkSchema(): void
    {
        if (!$this->schema instanceof Schema) {
            throw new RuntimeException('There is no Schema to Doc generation.');
        }
    }

    protected function normalizeType(?string $type)
    {
        if (0 === strpos($type, 'varchar')) {
            $type = 'varchar';
        }

        if (0 === strpos($type, 'enum')) {
            $type = 'enum';
        }

        if (0 === strpos($type, 'int')) {
            $type = 'int';
        }

        if (!array_key_exists($type, $this->getType())) {
            throw new RuntimeException('Unknown Type');
        }

        $type = $this->getType()[$type];

        return $type;
    }

    protected function checkDirectory(): void
    {
        if (
            !is_dir($this->pathOutput) &&
            !mkdir($concurrentDirectory = $this->pathOutput, 0777, true) &&
            !is_dir($concurrentDirectory)
        ) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    protected function deleteObsoleteFiles(): void
    {
        array_map('unlink', glob("$this->pathOutput/*.*"));
    }

    protected function deleteObsoleteFilesBundled(bool $bundle = false): void
    {
        // Deletion of the existing files
        $path = $this->pathOutput . '/' . static::BUNDLE_FILE . '.' . static::SCHEMA_EXT;

        if ($bundle) {
            if (is_file($path)) {
                unlink($path);
            }
        } else {
            array_map(
                function ($a) use ($path) {
                    if ($path !== $a) {
                        unlink($a);
                    }
                },
                glob($this->pathOutput . '/*.*')
            );
        }
    }
}
