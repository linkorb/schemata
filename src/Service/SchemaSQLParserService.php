<?php

namespace LinkORB\Schemata\Service;

use LinkORB\Schemata\Entity\Column;
use LinkORB\Schemata\Entity\Schema;
use LinkORB\Schemata\Entity\Table;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;

class SchemaSQLParserService
{
    /**
     * Reserved MySQL words to avoid skipping
     */
    private const RESERVED_WORDS = [
        'INDEX',
        'KEY',
        'CHAR',
        'GROUP',
        'TABLE',
        'FULLTEXT',
    ];

    private const RESERVED_WORDS_PREFIX = 'TMP_FIELD_PREFIX_';

    /**
     * @var array
     */
    private $preparedReservedWords;

    /**
     * @var array
     */
    private $reservedWordsReplacements;

    /**
     * @var string
     */
    private $pathSQL;

    public function __construct($pathSQL)
    {
        $this->pathSQL = $pathSQL;
    }

    public function parse(ProgressBar $progressBar): Schema
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->pathSQL)
            ->name(['*.sql']);

        $progressBar->setMaxSteps($finder->count());
        $progressBar->setRedrawFrequency(intdiv($finder->count(), 10));
        $progressBar->start();

        $schema = new Schema();

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $query = $this->normalizeToCreateTable($contents);

            $table = $this->getTableStructure($query);

            $schema->setTable($table);
            $progressBar->advance();
        }

        return $schema;
    }

    private function normalizeToCreateTable($contents): string
    {
        $contents = $this->excludeBOM($contents);

        // Remove hidden symbols
        $contents = preg_replace('/[\x00]/', '', $contents);

        preg_match('/(CREATE TABLE.*)GO/s', $contents, $matches);

        if (empty($matches[1])) {
            throw new RuntimeException("Can't parse 'CREATE TABLE' Statement.");
        }

        // Replace Reserved Words
        $statementString = str_replace(
            $this->prepareReservedWords(),
            $this->prepareReservedWordsReplacements(),
            $matches[1]
        );

        // Remove Identity
        $statementString = preg_replace('/IDENTITY\(\d,\d\)/i', '', $statementString);

        return str_replace(['[', ']'], '', $statementString);
    }

    private function getTableStructure($query): Table
    {
        $parser = new Parser($query);

        if (empty($parser->statements)) {
            throw new RuntimeException("Can't find any statements.");
        }

        $statement = $parser->statements[0];

        if (!($statement instanceof CreateStatement)) {
            throw new RuntimeException('Wrong Statement.');
        }

        $table = new Table();
        $table->setName($statement->name->table);

        foreach ($statement->fields as $field) {
            if (empty($field->type)) { // Skip Constraint
                continue;
            }

            $parameters = '';
            if (!empty($field->type->parameters)) {
                $parameters = '(' . implode(', ', $field->type->parameters) . ')';
            }

            $column = new Column();
            $column->setName(str_replace(self::RESERVED_WORDS_PREFIX, '', $field->name));
            $column->setType($field->type->name . $parameters);
            $table->addColumns([$column]);
        }

        return $table;
    }

    private function excludeBOM($contents)
    {
        if (mb_strpos($contents, "\xef\xbb\xbf") !== 0) {
            $contents = substr($contents, 2);
        }

        return $contents;
    }

    private function prepareReservedWords(): array
    {
        if (null !== $this->preparedReservedWords) {
            return $this->preparedReservedWords;
        }

        $res = [];

        foreach (self::RESERVED_WORDS as $word) {
            $res[] = '[' . $word . ']';
        }

        $this->preparedReservedWords = $res;

        return $this->preparedReservedWords;
    }

    private function prepareReservedWordsReplacements(): array
    {
        if (null !== $this->reservedWordsReplacements) {
            return $this->reservedWordsReplacements;
        }

        $res = [];

        foreach (self::RESERVED_WORDS as $word) {
            $res[] = '[' . self::RESERVED_WORDS_PREFIX . $word . ']';
        }

        $this->reservedWordsReplacements = $res;

        return $this->reservedWordsReplacements;
    }
}
