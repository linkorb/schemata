<?php

namespace LinkORB\Schemata\Entity;

use RuntimeException;

class Issue
{
    private const PARENT_TYPE_TABLE  = 'parentTable';
    private const PARENT_TYPE_COLUMN = 'parentColumn';

    public const STATUS_OPEN    = 'open';
    public const STATUS_CLOSED  = 'closed';
    public const STATUS_UNKNOWN = 'unknown';

    private $parentType;

    /**
     * @var Table|Column
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
     * @param $parent Table|Column
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
     * @param Table|Column $parent
     * @return Issue
     */
    private function setParent($parent): Issue
    {
        $this->parent = $parent;

        if ($parent instanceof Table) {
            $this->setParentType(self::PARENT_TYPE_TABLE);
        } else if ($parent instanceof Column) {
            $this->setParentType(self::PARENT_TYPE_COLUMN);
        } else {
            throw new RuntimeException('Unknown Parent Type');
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * @param mixed $parentType
     */
    private function setParentType($parentType): void
    {
        $this->parentType = $parentType;
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
