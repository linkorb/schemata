<?php

namespace LinkORB\Schemata\Command;

use LinkORB\Schemata\Service\DocGeneratorService;
use LinkORB\Schemata\Service\SchemaPagesParserService;
use LinkORB\Schemata\Service\SchemaProviderPath;
use LinkORB\Schemata\Service\SchemaService;
use Parsedown;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateHtmlDocCommand extends Command
{
    private const ARGUMENT_INPUT_PATH  = 'inputPath';
    private const ARGUMENT_OUTPUT_PATH = 'outputPath';

    protected function configure(): void
    {
        $this->setName('generate:html-doc')
            ->setDescription('HTML Doc generation.')
            ->setHelp('This command allows you to parse schema and generate HTML Doc.')
            ->addArgument(
                self::ARGUMENT_INPUT_PATH,
                InputArgument::REQUIRED,
                'Schema Directory Path'
            )
            ->addArgument(
                self::ARGUMENT_OUTPUT_PATH,
                InputArgument::REQUIRED,
                'HTML Doc Output Path'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the Schema parsing...');

        $schemaProvider = new SchemaProviderPath($input->getArgument(self::ARGUMENT_INPUT_PATH));

        $service = new SchemaService($schemaProvider->getSchema());

        $service->parseSchema();

        $schema = $service->getSchema();

        $generator = new DocGeneratorService(
            $schema,
            $input->getArgument(self::ARGUMENT_OUTPUT_PATH)
        );

        $generator->generate();

        $pagesParser = new SchemaPagesParserService(
            $input->getArgument(self::ARGUMENT_INPUT_PATH),
            new Parsedown()
        );

        $pages = $pagesParser->parse();

        $generator->generatePages($pages);

        $output->writeln([
            'Schema has been parsed successfully.',
            'Number of tables: ' . count($schema->getTables()),
            'Number of codelists: ' . count($schema->getCodelists()),
            'Number of pages: ' . count($pages),
        ]);
    }
}
