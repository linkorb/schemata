<?php

namespace Schemata\Entity;

use Schemata\Validators\CamelCaseLower;
use Schemata\Validators\SQLIdentifier;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Field
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
    private $foreignType;

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

    /** @var Tag[] */
    private $tags = [];

    /**
     * @var ConstraintViolation[]
     */
    private $violations = [];

    /**
     * @var Issue[]
     */
    private $issues = [];

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata
            ->addPropertyConstraint('name', new SQLIdentifier())
            ->addPropertyConstraint('alias', new CamelCaseLower());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Field
     */
    public function setName(string $name): Field
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
     * @return Field
     */
    public function setType(string $type): Field
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
     * @return Field
     */
    public function setLabel(string $label): Field
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
     * @return Field
     */
    public function setDoc(string $doc): Field
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
     * @return Field
     */
    public function setForeignKey(string $foreignKey): Field
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getForeignType(): ?string
    {
        return $this->foreignType;
    }

    /**
     * @param string $foreignType
     * @return Field
     */
    public function setForeignType(string $foreignType): Field
    {
        $this->foreignType = $foreignType;

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
     * @return Field
     */
    public function setCodelist(string $codelist): Field
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
     * @param string $alias
     * @return Field
     */
    public function setAlias(string $alias): Field
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
     * @return Field
     */
    public function setProperties(array $properties): Field
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
     * @return Field
     */
    public function setUnique(bool $unique): Field
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
     * @param bool $generated
     * @return Field
     */
    public function setGenerated(bool $generated): Field
    {
        $this->generated = $generated;

        return $this;
    }

    public function addTag(Tag $tag): void
    {
        $this->tags[$tag->getName()] = $tag;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return array
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @param ConstraintViolation $violation
     * @return Field
     */
    public function addViolation(ConstraintViolation $violation): Field
    {
        $this->violations[] = $violation;

        return $this;
    }

    public function cleanUpViolations(): void
    {
        $this->violations = [];
    }

    /**
     * @return Issue[]
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @param Issue[] $issues
     * @return Field
     */
    public function setIssues(array $issues): Field
    {
        foreach ($issues as $issue) {
            $this->issues[] = $issue;
        }

        return $this;
    }
}
