<?php

namespace Schemata\Entity;

use RuntimeException;

class Issue
{
    private const PARENT_CLASS_TYPE  = 'parentType';
    private const PARENT_CLASS_COLUMN = 'parentField';

    public const STATUS_OPEN    = 'open';
    public const STATUS_CLOSED  = 'closed';
    public const STATUS_UNKNOWN = 'unknown';

    private $parentClass;

    /**
     * @var Type|Field
     */
    private $parent;

    private $type;

    private $status = self::STATUS_UNKNOWN;

    /**
     * @var Note[]
     */
    private $notes = [];

    /**
     * Issue constructor.
     * @param $parent Type|Field
     */
    public function __construct($parent)
    {
        $this->setParent($parent);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Type|Field $parent
     * @return Issue
     */
    private function setParent($parent): Issue
    {
        $this->parent = $parent;

        if ($parent instanceof Type) {
            $this->setParentClass(self::PARENT_CLASS_TYPE);
        } else if ($parent instanceof Field) {
            $this->setParentClass(self::PARENT_CLASS_COLUMN);
        } else {
            throw new RuntimeException('Unknown Parent Class');
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParentClass()
    {
        return $this->parentClass;
    }

    /**
     * @param mixed $parentClass
     */
    private function setParentClass($parentClass): void
    {
        $this->parentClass = $parentClass;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return Issue
     */
    public function setType($type): Issue
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return Issue
     */
    public function setStatus($status): Issue
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Note[]
     */
    public function getNotes(): array
    {
        return $this->notes;
    }

    /**
     * @param Note[] $notes
     * @return Issue
     */
    public function setNotes(array $notes): Issue
    {
        $this->notes = $notes;

        return $this;
    }

    public function isOpen(): bool
    {
        return !(self::STATUS_CLOSED === $this->status);
    }
}
