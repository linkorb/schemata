<?php

namespace LinkORB\Schemata\Service;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;

class SchemaSQLParserService
{
    /**
     * @var string
     */
    private $pathSQL;

    /**
     * @var CreateStatement[]
     */
    private $tables = [];

    public function __construct($pathSQL)
    {
        $this->pathSQL = $pathSQL;
    }

    public function parse(ProgressBar $progressBar): array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->pathSQL)
            ->name(['*.sql']);

        $progressBar->setMaxSteps($finder->count());
        $progressBar->setRedrawFrequency(intdiv($finder->count(), 10));
        $progressBar->start();

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $query = $this->normalizeToCreateTable($contents);

            $table = $this->getTableStructure($query);

            $this->tables[] = [
                'table' => [
                    '@name'  => $table['name'],
                    'column' => $table['columns'],
                ],
            ];

            $progressBar->advance();
        }

        return $this->tables;
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
        $statementString = str_replace('[INDEX]', '[TMP_FIELD_PREFIX_INDEX]', $matches[1]);

        // Remove Identity
        $statementString = preg_replace('/IDENTITY\(\d,\d\)/i', '', $statementString);

        return str_replace(['[', ']'], '', $statementString);
    }

    private function getTableStructure($query): array
    {
        $parser = new Parser($query);

        if (empty($parser->statements)) {
            throw new RuntimeException("Can't find any statements.");
        }

        $statement = $parser->statements[0];

        if (!($statement instanceof CreateStatement)) {
            throw new RuntimeException('Wrong Statement.');
        }

        $res = [
            'name'    => $statement->name->table,
            'columns' => [],
        ];

        foreach ($statement->fields as $field) {
            if (empty($field->type)) { // Skip Constraint
                continue;
            }

            $parameters = '';
            if (!empty($field->type->parameters)) {
                $parameters = '(' . implode(', ', $field->type->parameters) . ')';
            }

            $res['columns'][] = [
                '@name' => str_replace('TMP_FIELD_PREFIX_', '', $field->name),
                '@type' => $field->type->name . $parameters,
            ];
        }

        return $res;
    }

    private function excludeBOM($contents)
    {
        if (mb_strpos($contents, "\xef\xbb\xbf") !== 0) {
            $contents = substr($contents, 2);
        }

        return $contents;
    }
}
