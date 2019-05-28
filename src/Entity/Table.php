<?php

namespace LinkORB\Schema\Entity;

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

    public function addColumns(array $columns): void
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


    public function addTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }
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
                    $aliasTotal ++;
                }
                $total++;
            }
        }
        if ($total==0) {
            return 100;
        }
        return round(100/$total * $aliasTotal);
    }

    public function getColumnAliasPercentageClass()
    {
        $percentage = $this->getColumnAliasPercentage();
        if ($percentage==0) {
            return 'secondary';
        }
        if ($percentage==100) {
            return 'success';
        }
        return 'warning';
    }
}
