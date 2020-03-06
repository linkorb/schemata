<?php

namespace Schemata\Entity;

class Property
{
    /** @var PropertyDefinition */
    private $definition;

    private $value;

    public function __construct(PropertyDefinition $definition, $value)
    {
        $this->definition = $definition;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getDefinition(): PropertyDefinition
    {
        return $this->definition;
    }

}
