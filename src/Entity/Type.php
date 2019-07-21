<?php

namespace LinkORB\Schemata\Entity;

use LinkORB\Schemata\Validators\CamelCaseUpper;
use LinkORB\Schemata\Validators\SQLIdentifier;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Type
{
    /** @var string */
    private $name;

    /** @var string */
    private $alias;

    /** @var Field[] */
    private $fields = [];

    /** @var Tag[] */
    private $tags = [];

    /** @var array */
    private $properties = [];

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
            ->addPropertyConstraint('alias', new CamelCaseUpper());
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
     * @return Type
     */
    public function setName(string $name): Type
    {
        $this->name = $name;

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
     * @return Type
     */
    public function setAlias(string $alias): Type
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param Field[] $fields
     */
    public function addFields(array $fields): void
    {
        foreach ($fields as $field) {
            $name = $field->getName();

            if (!array_key_exists($name, $this->fields)) {
                $this->fields[$name] = $field;
            }
        }
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
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
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return Type
     */
    public function setProperties(array $properties): Type
    {
        $this->properties = $properties;

        return $this;
    }

    public function getFieldAliasPercentage()
    {
        $total = 0;
        $aliasTotal = 0;
        foreach ($this->fields as $c) {
            if (!$c->isGenerated()) {
                if ($c->getAlias()) {
                    $aliasTotal++;
                }
                $total++;
            }
        }
        if ($total === 0) {
            return 100;
        }

        return round(100 / $total * $aliasTotal);
    }

    public function getFieldAliasPercentageClass(): string
    {
        $percentage = $this->getFieldAliasPercentage();
        if ($percentage === 0) {
            return 'secondary';
        }
        if ($percentage === 100) {
            return 'success';
        }

        return 'warning';
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
     * @return Type
     */
    public function addViolation(ConstraintViolation $violation): Type
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
     * @return Type
     */
    public function setIssues(array $issues): Type
    {
        foreach ($issues as $issue) {
            $this->issues[] = $issue;
        }

        return $this;
    }
}
