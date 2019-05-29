<?php

namespace LinkORB\Schemata\Entity;

class Column
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $label;

    /** @var string */
    private $doc;

    /** @var string */
    private $foreignKey;

    /** @var string */
    private $foreignTable;

    /** @var string */
    private $codelist;

    /** @var string */
    private $alias;

    /** @var bool */
    private $unique = false;

    /** @var bool */
    private $generated = false;

    /** @var array */
    private $properties = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Column
     */
    public function setName(string $name): Column
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Column
     */
    public function setType(string $type): Column
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return Column
     */
    public function setLabel(string $label): Column
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getDoc(): ?string
    {
        return $this->doc;
    }

    /**
     * @param string $doc
     * @return Column
     */
    public function setDoc(string $doc): Column
    {
        $this->doc = $doc;

        return $this;
    }

    /**
     * @return string
     */
    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }

    /**
     * @param string $foreignKey
     * @return Column
     */
    public function setForeignKey(string $foreignKey): Column
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getForeignTable(): ?string
    {
        return $this->foreignTable;
    }

    /**
     * @param string $foreignTable
     * @return Column
     */
    public function setForeignTable(string $foreignTable): Column
    {
        $this->foreignTable = $foreignTable;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodelist(): ?string
    {
        return $this->codelist;
    }

    /**
     * @param string $codelist
     * @return Column
     */
    public function setCodelist(string $codelist): Column
    {
        $this->codelist = $codelist;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string $name
     * @return Column
     */
    public function setAlias(string $alias): Column
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return Column
     */
    public function setProperties(array $properties): Column
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     * @return Column
     */
    public function setUnique(bool $unique): Column
    {
        $this->unique = $unique;

        return $this;
    }

    /**
     * @return bool
     */
    public function isGenerated(): bool
    {
        return $this->generated;
    }

    /**
     * @param bool $unique
     * @return Column
     */
    public function setGenerated(bool $generated): Column
    {
        $this->generated = $generated;

        return $this;
    }
}
