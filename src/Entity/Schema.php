<?php

namespace Schemata\Entity;

use RuntimeException;

class Schema
{
    /**
     * @var Type[]
     */
    private $types = [];

    /**
     * @var Codelist[]
     */
    private $codelists = [];

    /**
     * @var array
     */
    private $taggedTypes = [];

    /**
     * @var Type[]
     */
    private $typesWithIssues = [];

    public function getTaggedTypes(): array
    {
        return $this->taggedTypes;
    }

    public function getTagsAll(): array
    {
        return array_keys($this->taggedTypes);
    }

    public function addTaggedType(Tag $tag, Type $type): void
    {
        $this->taggedTypes[$tag->getName()][$type->getName()] = $type;
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getType($typeName): Type
    {
        if (array_key_exists($typeName, $this->types)) {
            return $this->types[$typeName];
        }

        throw new RuntimeException('Type does not exist.');
    }

    public function setType(Type $type): Schema
    {
        $this->types[$type->getName()] = $type;

        return $this;
    }

    public function addCodelist(Codelist $codelist): void
    {
        if (array_key_exists($codelist->getName(), $this->codelists)) {
            throw new RuntimeException('Codelist duplication: "' . $codelist->getName() . '"');
        }

        $this->codelists[$codelist->getName()] = $codelist;
    }

    public function addCodelistAsType(Type $type): void
    {
        if (array_key_exists($type->getName(), $this->types)) {
            throw new RuntimeException('Codelist duplication: ' . $type->getName());
        }

        $this->setType($type);
    }

    /**
     * @return Codelist[]
     */
    public function getCodelists(): array
    {
        return $this->codelists;
    }

    public function addTypeWithIssues(Type $type): void
    {
        if (!array_key_exists($type->getName(), $this->typesWithIssues)) {
            $this->typesWithIssues[$type->getName()] = $type;
        }
    }

    /**
     * @return Type[]
     */
    public function getTypesWithIssues(): array
    {
        return $this->typesWithIssues;
    }

    public function cleanUpForDiff(): void
    {
        $this->taggedTypes = [];

        $this->typesWithIssues = [];

        foreach ($this->types as $type) {
            $type->cleanUpViolations();
            foreach ($type->getFields() as $column) {
                $column->cleanUpViolations();
            }
        }
    }
}
