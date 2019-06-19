<?php

namespace LinkORB\Schemata\Entity;

use DateTime;
use LinkORB\Schemata\Validators\CamelCaseUpper;
use LinkORB\Schemata\Validators\SQLIdentifier;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Table
{
    /** @var string */
    private $name;

    /** @var string */
    private $alias;

    /** @var Column[] */
    private $columns = [];

    /** @var Tag[] */
    private $tags = [];

    /** @var array */
    private $properties = [];

    /**
     * @var ConstraintViolation[]
     */
    private $violations = [];

    /**
     * @var array
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
     * @return Table
     */
    public function setName(string $name): Table
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
     * @return Table
     */
    public function setAlias(string $alias): Table
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param Column[] $columns
     */
    public function addColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $name = $column->getName();

            if (!array_key_exists($name, $this->columns)) {
                $this->columns[$name] = $column;
            }
        }
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
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
     * @return Table
     */
    public function setProperties(array $properties): Table
    {
        $this->properties = $properties;

        return $this;
    }

    public function getColumnAliasPercentage()
    {
        $total = 0;
        $aliasTotal = 0;
        foreach ($this->columns as $c) {
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

    public function getColumnAliasPercentageClass(): string
    {
        $percentage = $this->getColumnAliasPercentage();
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
     * @return Table
     */
    public function addViolation(ConstraintViolation $violation): Table
    {
        $this->violations[] = $violation;

        return $this;
    }

    public function cleanUpViolations(): void
    {
        $this->violations = [];
    }

    /**
     * @return array
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @param array $issues
     * @return Table
     */
    public function setIssues(array $issues): Table
    {
        foreach ($issues as $idx => $issue) {
            if (!isset($issue['note'][0])) {
                $issues[$idx]['note'] = [$issue['note']];
            }

            usort(
                $issues[$idx]['note'],
                static function ($a, $b) {
                    if ($a['@createdAt'] === $b['@createdAt']) {
                        return 0;
                    }

                    return ($a['@createdAt'] < $b['@createdAt']) ? -1 : 1;
                }
            );

            foreach ($issues[$idx]['note'] as $idxNote => $note) {
                if (!empty($note['@createdAt'])) {
                    $issues[$idx]['note'][$idxNote]['@createdAt'] = DateTime::createFromFormat('Ymd', $note['@createdAt']);
                }
            }
        }

        $this->issues = $issues;

        return $this;
    }
}
