<?php

namespace LinkORB\Schemata\Entity;

use LinkORB\Schemata\Validators\CamelCaseUpper;
use LinkORB\Schemata\Validators\SQLIdentifier;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Table
{
    /** @var string */
    private $name;

    /** @var string */
    private $alias;

    /** @var string */
    private $description;

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

    public function addColumns(array $columns, ValidatorInterface $validator): void
    {
        foreach ($columns as $column) {
            $name = $column['@name'];

            if (!array_key_exists($name, $this->columns)) {
                $newColumn = new Column();

                $newColumn->setName($name);
                $newColumn->setProperties($this->getCustomProperties($column));

                if (isset($column['@type'])) {
                    $newColumn->setType($column['@type']);
                }

                if (isset($column['@label'])) {
                    $newColumn->setLabel($column['@label']);
                }

                if (isset($column['@alias'])) {
                    $newColumn->setAlias($column['@alias']);
                }
                if (isset($column['@generated'])) {
                    $newColumn->setGenerated($column['@generated']);
                }

                if (isset($column['@doc'])) {
                    $newColumn->setDoc($column['@doc']);
                }

                if (isset($column['@foreignkey'])) {
                    $keys = explode('.', $column['@foreignkey']);
                    if (2 === count($keys)) {
                        $newColumn->setForeignTable($keys[0]);
                    }
                    $newColumn->setForeignKey($column['@foreignkey']);
                }

                if (isset($column['@codelist'])) {
                    $newColumn->setCodelist($column['@codelist']);
                    $newColumn->setType('codelist');
                    $newColumn->setForeignTable('codelist__' . $newColumn->getCodelist());
                }

                if (isset($column['@unique']) && is_bool($column['@unique'])) {
                    $newColumn->setUnique($column['@unique']);
                }

                if (isset($column['@tags'])) {
                    $tagNames = explode(',', $column['@tags']);
                    foreach ($tagNames as $tagName) {
                        $tagName = trim($tagName);
                        if (!empty($tagName)) {
                            $tag = new Tag();
                            $tag->setName($tagName);
                            $newColumn->addTag($tag);
                        }
                    }
                }

                /** @var ConstraintViolationList $errors */
                $errors = $validator->validate($newColumn);

                if (0 < $errors->count()) {
                    $iterator = $errors->getIterator();

                    foreach ($iterator as $violationItem) {
                        $newColumn->addViolation($violationItem);
                    }
                }

                $this->columns[$name] = $newColumn;
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

    private function getCustomProperties($column): array
    {
        $properties = [];

        foreach ($column as $key => $value) {
            if (0 === strpos($key, '@p:')) {
                $property = str_replace('@p:', '', $key);
                $properties[$property] = $value;
            }
        }

        return $properties;
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
}
