<?php

namespace Schemata\Entity;

use Schemata\Schemata;

class PropertyDefinition
{
    /** @var string */
    private $name;

    private $options;
    private $localized;
    private $indexed;
    private $classes;

    public function __construct(string $name, array $options)
    {
        $this->name = $name;
        $this->localized = $options['localized'] ?? false;
        $this->indexed = $options['indexed'] ?? false;
        foreach ($options['classes'] ?? [] as $className) {
            $classId = Schemata::classNameToId($className);
            $this->classes[] = $classId;
        }
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getClassesAsString(): string
    {
        $res = '';
        foreach ($this->classes as $classId) {
            $res .= Schemata::classIdToName($classId). ', ';
        }
        return trim($res, ', ');
    }

    public function hasClass(int $classId): bool
    {
        return in_array($classId, $this->classes);
    }

    public function isLocalized(): bool
    {
        return $this->localized;
    }

    public function isIndexed(): bool
    {
        return $this->indexed;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
