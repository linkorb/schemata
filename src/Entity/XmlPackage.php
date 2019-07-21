<?php

namespace Schemata\Entity;

use RuntimeException;

/**
 * Reflects single schema xml file as a package of tables
 *
 * Class XmlPackage
 * @package Schemata\Entity
 */
class XmlPackage
{
    private $tables = [];

    public function setTable($tables)
    {
        if (isset($tables[0])) {
            $this->tables = $tables;
        } else if (isset($tables['@name'])) {
            $this->tables[] = $tables;
        } else {
            throw new RuntimeException('Unknown Table Format.');
        }

        return $this;
    }

    public function getTables(): array
    {
        return $this->tables;
    }
}
