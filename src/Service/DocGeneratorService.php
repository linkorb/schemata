<?php

namespace LinkORB\Schemata\Service;

use LinkORB\Schemata\Entity\Schema;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class DocGeneratorService extends AbstractGeneratorService
{
    private const PATH_TEMPLATES = __DIR__ . '/../../templates';

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Schema $schema, $pathOutput)
    {
        parent::__construct($schema, $pathOutput);

        $loader = new FilesystemLoader(self::PATH_TEMPLATES);
        $this->twig = new Environment($loader);
    }

    public function generate(): void
    {
        $this->checkSchema();

        $this->checkDirectory();

        $this->deleteObsoleteFiles();

        $this->generateIndex();

        $this->generateTables();

        $this->generateColumns();

        $this->generateCodelists();

        $this->generateTaggedTables();

        $this->generateValidationIssues();
    }

    private function generateIndex(): void
    {
        file_put_contents(
            $this->pathOutput . '/index.html',
            $this->twig->render('index.html.twig'));
    }

    private function generateTables(): void
    {
        $tables = $this->schema->getTables();
        ksort($tables);

        $tagsAll = $this->schema->getTagsAll();
        ksort($tagsAll);

        file_put_contents(
            $this->pathOutput . '/tables.html',
            $this->twig->render('tables.html.twig', [
                'tables'  => $tables,
                'tagsAll' => $tagsAll,
            ]));

        foreach ($tables as $table) {
            file_put_contents(
                $this->pathOutput . '/table__' . $table->getName() . '.html',
                $this->twig->render('table.html.twig', [
                    'table' => $table,
                ])
            );
        }
    }

    private function generateColumns(): void
    {
        $tables = $this->schema->getTables();

        foreach ($tables as $table) {
            $columns = $table->getColumns();
            foreach ($columns as $column) {
                file_put_contents(
                    $this->pathOutput . '/column__' . $table->getName() . '__' . $column->getName() . '.html',
                    $this->twig->render('column.html.twig', [
                        'column'    => $column,
                        'tableName' => $table->getName(),
                    ])
                );
            }
        }
    }

    private function generateCodelists(): void
    {
        $codelists = $this->schema->getCodelists();
        ksort($codelists);

        file_put_contents(
            $this->pathOutput . '/codelists.html',
            $this->twig->render('codelists.html.twig', [
                'codelists' => $codelists,
            ])
        );

        foreach ($codelists as $codelist) {
            file_put_contents(
                $this->pathOutput . '/codelist__' . $codelist->getName() . '.html',
                $this->twig->render('codelist.html.twig', [
                    'name'  => $codelist->getName(),
                    'items' => $codelist->getItems(),
                ])
            );
        }
    }

    private function generateTaggedTables(): void
    {
        foreach ($this->schema->getTaggedTables() as $tagName => $taggedTables) {
            file_put_contents(
                $this->pathOutput . '/tables__tag_' . $tagName . '.html',
                $this->twig->render('tables.html.twig', [
                    'tables' => $taggedTables,
                ])
            );
        }
    }

    private function generateValidationIssues(): void
    {
        file_put_contents(
            $this->pathOutput . '/validation-issues.html',
            $this->twig->render('validation-issues.html.twig', [
                'tables' => $this->schema->getTablesWithIssues(),
            ]));
    }
}
