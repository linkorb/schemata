<?php

namespace Schemata\Service;

use Schemata\Entity\Issue;
use Schemata\Entity\Schema;
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

        $this->generateTypes();

        $this->generateFields();

        $this->generateCodelists();

        $this->generatePropertyDefinitions();

        $this->generateTaggedTypes();

        $this->generateValidationIssues();

        $this->generateRegularIssues();
    }

    private function generateTypes(): void
    {
        $types = $this->schema->getTypes();
        ksort($types);

        $tagsAll = $this->schema->getTagsAll();
        ksort($tagsAll);

        file_put_contents(
            $this->pathOutput . '/types.html',
            $this->twig->render('types.html.twig', [
                'types'  => $types,
                'tagsAll' => $tagsAll,
            ]));

        foreach ($types as $type) {
            file_put_contents(
                $this->pathOutput . '/type__' . $type->getName() . '.html',
                $this->twig->render('type.html.twig', [
                    'type' => $type,
                ])
            );
        }
    }

    private function generateFields(): void
    {
        $types = $this->schema->getTypes();

        foreach ($types as $type) {
            $fields = $type->getFields();
            foreach ($fields as $field) {
                file_put_contents(
                    $this->pathOutput . '/field__' . $type->getName() . '__' . $field->getName() . '.html',
                    $this->twig->render('field.html.twig', [
                        'field'    => $field,
                        'typeName' => $type->getName(),
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

    private function generatePropertyDefinitions(): void
    {
        $definitions = $this->schema->getPropertyDefinitions();
        ksort($definitions);

        file_put_contents(
            $this->pathOutput . '/property-definitions.html',
            $this->twig->render('property-definitions.html.twig', [
                'propertyDefinitions' => $definitions,
            ])
        );

        foreach ($definitions as $definition) {
            file_put_contents(
                $this->pathOutput . '/property-definition__' . $definition->getName() . '.html',
                $this->twig->render('property-definition.html.twig', [
                    'propertyDefinition'  => $definition,
                ])
            );
        }
    }

    private function generateTaggedTypes(): void
    {
        foreach ($this->schema->getTaggedTypes() as $tagName => $taggedTypes) {
            file_put_contents(
                $this->pathOutput . '/types__tag_' . $tagName . '.html',
                $this->twig->render('types.html.twig', [
                    'types' => $taggedTypes,
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
                    'types' => $this->schema->getTypesWithIssues(),
                ]
            )
        );
    }

    private function generateRegularIssues(): void
    {
        $issuesOpen = [];
        $issuesClosed = [];

        $types = $this->schema->getTypesWithIssues();

        foreach ($types as $type) {
            foreach ($type->getIssues() as $idxTypeIssue => $typeIssue) {
                if ($typeIssue->isOpen()) {
                    $issuesOpen[$type->getName()]['type'][$idxTypeIssue] = $typeIssue;
                } else {
                    $issuesClosed[$type->getName()]['type'][$idxTypeIssue] = $typeIssue;
                }

                $this->generateTypeIssue($typeIssue, $idxTypeIssue);
            }

            foreach ($type->getFields() as $field) {
                foreach ($field->getIssues() as $idxFieldIssue => $fieldIssue) {
                    if ($fieldIssue->isOpen()) {
                        $issuesOpen[$type->getName()]['field'][$field->getName()][$idxFieldIssue] = $fieldIssue;
                    } else {
                        $issuesClosed[$type->getName()]['field'][$field->getName()][$idxFieldIssue] = $fieldIssue;
                    }

                    $this->generateFieldIssue($fieldIssue, $type->getName(), $idxFieldIssue);
                }
            }
        }

        file_put_contents(
            $this->pathOutput . '/issues-open.html',
            $this->twig->render(
                'issues.html.twig',
                [
                    'issues' => $issuesOpen,
                    'isOpen' => true,
                ]
            )
        );

        file_put_contents(
            $this->pathOutput . '/issues-closed.html',
            $this->twig->render(
                'issues.html.twig',
                [
                    'issues' => $issuesClosed,
                    'isOpen' => false,
                ]
            )
        );
    }

    private function generateTypeIssue(Issue $issue, $idx): void
    {
        file_put_contents(
            $this->pathOutput . '/issue__' . $issue->getParent()->getName() . '__' . $idx . '.html',
            $this->twig->render(
                'issue-type.html.twig',
                [
                    'issue' => $issue,
                    'idx'   => $idx,
                ]
            )
        );
    }

    private function generateFieldIssue(Issue $issue, $typeName, $idx): void
    {
        file_put_contents(
            $this->pathOutput . '/issue__' . $typeName . '__' . $issue->getParent()->getName() . '__' . $idx . '.html',
            $this->twig->render(
                'issue-field.html.twig',
                [
                    'issue'     => $issue,
                    'typeName' => $typeName,
                    'idx'       => $idx,
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
