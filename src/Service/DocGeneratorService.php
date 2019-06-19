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

        $this->generateTables();

        $this->generateColumns();

        $this->generateCodelists();

        $this->generateTaggedTables();

        $this->generateValidationIssues();

        $this->generateRegularIssues();
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
            $this->twig->render(
                'validation-issues.html.twig',
                [
                    'tables' => $this->schema->getTablesWithIssues(),
                ]
            )
        );
    }

    private function generateRegularIssues(): void
    {
        $issuesOpen = [];
        $issuesClosed = [];

        $tables = $this->schema->getTablesWithIssues();

        foreach ($tables as $table) {
            foreach ($table->getIssues() as $idxTableIssue => $tableIssue) {
                $isOpen = true;
                if (
                    !isset($tableIssue['@status']) ||
                    !in_array($tableIssue['@status'], ['open', 'closed'])
                ) {
                    $tableIssue['@status'] = 'unknown';
                }

                if ('closed' === $tableIssue['@status']) {
                    $issuesClosed[$table->getName()]['table'][$idxTableIssue] = $tableIssue;
                    $isOpen = false;
                } else {
                    $issuesOpen[$table->getName()]['table'][$idxTableIssue] = $tableIssue;
                }

                $this->generateTableIssue($tableIssue, $table->getName(), $idxTableIssue, $isOpen);
            }

            foreach ($table->getColumns() as $column) {
                foreach ($column->getIssues() as $idxColumnIssue => $columnIssue) {
                    $isOpen = true;
                    if (
                        !isset($columnIssue['@status']) ||
                        !in_array($columnIssue['@status'], ['open', 'closed'])
                    ) {
                        $columnIssue['@status'] = 'unknown';
                    }

                    if ('closed' === $columnIssue['@status']) {
                        $issuesClosed[$table->getName()]['column'][$column->getName()][$idxColumnIssue] = $columnIssue;
                        $isOpen = false;
                    } else {
                        $issuesOpen[$table->getName()]['column'][$column->getName()][$idxColumnIssue] = $columnIssue;
                    }

                    $this->generateColumnIssue($columnIssue, $table->getName(), $column->getName(), $idxColumnIssue, $isOpen);
                }
            }
        }

        file_put_contents(
            $this->pathOutput . '/issues-open.html',
            $this->twig->render(
                'issues-open.html.twig',
                [
                    'issues' => $issuesOpen,
                ]
            )
        );

        file_put_contents(
            $this->pathOutput . '/issues-closed.html',
            $this->twig->render(
                'issues-closed.html.twig',
                [
                    'issues' => $issuesClosed,
                ]
            )
        );
    }

    private function generateTableIssue($issue, $tableName, $idx, bool $isOpen): void
    {
        file_put_contents(
            $this->pathOutput . '/issue__' . $tableName . '__' . $idx . '.html',
            $this->twig->render(
                'issue-table.html.twig',
                [
                    'issue'     => $issue,
                    'tableName' => $tableName,
                    'idx'       => $idx,
                    'isOpen'    => $isOpen,
                ]
            )
        );
    }

    private function generateColumnIssue($issue, $tableName, $columnName, $idx, bool $isOpen): void
    {
        file_put_contents(
            $this->pathOutput . '/issue__' . $tableName . '__' . $columnName . '__' . $idx . '.html',
            $this->twig->render(
                'issue-column.html.twig',
                [
                    'issue'      => $issue,
                    'tableName'  => $tableName,
                    'columnName' => $columnName,
                    'idx'        => $idx,
                    'isOpen'     => $isOpen,
                ]
            )
        );
    }

    public function generatePages(array $pages): void
    {
        foreach ($pages as $name => $contents) {
            file_put_contents(
                $this->pathOutput . '/' . $name,
                $this->twig->render(
                    'page.html.twig',
                    [
                        'contents' => $contents,
                    ]
                )
            );
        }

        $names = array_keys($pages);
        sort($names);

        file_put_contents(
            $this->pathOutput . '/pages.html',
            $this->twig->render(
                'pages.html.twig',
                [
                    'names' => $names,
                ]
            )
        );
    }
}
