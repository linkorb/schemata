<?php

namespace Schemata\Entity;

class Codelist
{
    /** @var string */
    private $name;

    /** @var array */
    private $items = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Codelist
     */
    public function setName(string $name): Codelist
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     * @return Codelist
     */
    public function setItems(array $items): Codelist
    {
        $this->items = $items;

        return $this;
    }
}
