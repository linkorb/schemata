<?php

namespace LinkORB\Schemata\Entity;

use RuntimeException;

class Schema
{
    /**
     * @var Table[]
     */
    private $tables = [];

    /**
     * @var Codelist[]
     */
    private $codelists = [];

    /**
     * @var array
     */
    private $taggedTables = [];

    /**
     * @var Table[]
     */
    private $tablesWithIssues = [];

    public function getTaggedTables(): array
    {
        return $this->taggedTables;
    }

    public function getTagsAll(): array
    {
        return array_keys($this->taggedTables);
    }

    public function addTaggedTable(Tag $tag, Table $table): void
    {
        $this->taggedTables[$tag->getName()][$table->getName()] = $table;
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function getTable($tableName): Table
    {
        if (array_key_exists($tableName, $this->tables)) {
            return $this->tables[$tableName];
        }

        throw new RuntimeException('Table does not exist.');
    }

    public function setTable(Table $table): Schema
    {
        $this->tables[$table->getName()] = $table;

        return $this;
    }

    public function addCodelist(Codelist $codelist): void
    {
        if (array_key_exists($codelist->getName(), $this->codelists)) {
            throw new RuntimeException('Codelist duplication: "' . $codelist->getName() . '"');
        }

        $this->codelists[$codelist->getName()] = $codelist;
    }

    public function addCodelistAsTable(Table $table): void
    {
        if (array_key_exists($table->getName(), $this->tables)) {
            throw new RuntimeException('Codelist duplication: ' . $table->getName());
        }

        $this->setTable($table);
    }

    /**
     * @return Codelist[]
     */
    public function getCodelists(): array
    {
        return $this->codelists;
    }

    public function addTableWithIssues(Table $table): void
    {
        if (!array_key_exists($table->getName(), $this->tablesWithIssues)) {
            $this->tablesWithIssues[$table->getName()] = $table;
        }
    }

    /**
     * @return Table[]
     */
    public function getTablesWithIssues(): array
    {
        return $this->tablesWithIssues;
    }

    public function cleanUpForDiff(): void
    {
        $this->taggedTables = [];

        $this->tablesWithIssues = [];

        foreach ($this->tables as $table) {
            $table->cleanUpViolations();
            foreach ($table->getColumns() as $column) {
                $column->cleanUpViolations();
            }
        }
    }
}
