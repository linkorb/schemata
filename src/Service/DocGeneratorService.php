<?php

namespace LinkORB\Schemata\Service;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class DocGeneratorService extends AbstractGeneratorService
{
    private const PATH_TEMPLATES = __DIR__ . '/../../templates';

    public function generate(): void
    {
        $this->checkSchema();

        $this->checkDirectory();

        $this->deleteObsoleteFiles();

        $loader = new FilesystemLoader(self::PATH_TEMPLATES);

        $twig = new Environment($loader);

        $this->generateIndex($twig);

        $this->generateTables($twig);

        $this->generateColumns($twig);

        $this->generateCodelists($twig);

        $this->generateTaggedTables($twig);

        $this->generateValidationIssues($twig);
    }

    protected function deleteObsoleteFiles(bool $bundle = false): void
    {
        array_map('unlink', glob("$this->pathOutput/*.*"));
    }

    private function generateIndex(Environment $twig): void
    {
        file_put_contents($this->pathOutput . '/index.html', $twig->render('index.html.twig'));
    }

    private function generateTables(Environment $twig): void
    {
        $tables = $this->schema->getTables();
        ksort($tables);

        $tagsAll = $this->schema->getTagsAll();
        ksort($tagsAll);

        file_put_contents(
            $this->pathOutput . '/tables.html',
            $twig->render('tables.html.twig', [
                'tables'  => $tables,
                'tagsAll' => $tagsAll,
            ]));

        foreach ($tables as $table) {
            file_put_contents(
                $this->pathOutput . '/table__' . $table->getName() . '.html',
                $twig->render('table.html.twig', [
                    'table' => $table,
                ])
            );
        }
    }

    private function generateColumns(Environment $twig): void
    {
        $tables = $this->schema->getTables();

        foreach ($tables as $table) {
            $columns = $table->getColumns();
            foreach ($columns as $column) {
                file_put_contents(
                    $this->pathOutput . '/column__' . $table->getName() . '__' . $column->getName() . '.html',
                    $twig->render('column.html.twig', [
                        'column'    => $column,
                        'tableName' => $table->getName(),
                    ])
                );
            }
        }
    }

    private function generateCodelists(Environment $twig): void
    {
        $codelists = $this->schema->getCodelists();
        ksort($codelists);

        file_put_contents(
            $this->pathOutput . '/codelists.html',
            $twig->render('codelists.html.twig', [
                'codelists' => $codelists,
            ])
        );

        foreach ($codelists as $codelist) {
            file_put_contents(
                $this->pathOutput . '/codelist__' . $codelist->getName() . '.html',
                $twig->render('codelist.html.twig', [
                    'name'  => $codelist->getName(),
                    'items' => $codelist->getItems(),
                ])
            );
        }
    }

    private function generateTaggedTables(Environment $twig): void
    {
        foreach ($this->schema->getTaggedTables() as $tagName => $taggedTables) {
            file_put_contents(
                $this->pathOutput . '/tables__tag_' . $tagName . '.html',
                $twig->render('tables.html.twig', [
                    'tables' => $taggedTables,
                ])
            );
        }
    }

    private function generateValidationIssues(Environment $twig): void
    {
        file_put_contents(
            $this->pathOutput . '/validation-issues.html',
            $twig->render('validation-issues.html.twig', [
                'tables' => $this->schema->getTablesWithIssues(),
            ]));
    }
}
