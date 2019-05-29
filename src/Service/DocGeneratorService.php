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

        file_put_contents($this->pathOutput . '/index.html', $twig->render('index.html.twig'));

        $tables = $this->schema->getTables();
        ksort($tables);

        file_put_contents($this->pathOutput . '/tables.html', $twig->render('tables.html.twig', [
            'tables' => $tables,
        ]));

        foreach ($tables as $table) {
            file_put_contents(
                $this->pathOutput . '/table__' . $table->getName() . '.html',
                $twig->render('table.html.twig', [
                    'table' => $table,
                ])
            );
        }

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

    protected function deleteObsoleteFiles(bool $bundle = false): void
    {
        array_map('unlink', glob("$this->pathOutput/*.*"));
    }
}
