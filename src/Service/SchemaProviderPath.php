<?php

namespace Schemata\Service;

use Schemata\Interfaces\SchemaProviderInterface;
use RuntimeException;

class SchemaProviderPath implements SchemaProviderInterface
{
    /**
     * Schema Directory Path
     *
     * @var string
     */
    private $path;

    public function __construct($path)
    {
        if (!file_exists($path)) {
            throw new RuntimeException('Path does not exist');
        }

        $this->path = $path;
    }

    public function getSchema(): string
    {
        return $this->path;
    }
}
