<?php

namespace LinkORB\Schema\Command;

use LinkORB\Schema\Service\DocGeneratorService;
use LinkORB\Schema\Service\SchemaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateHtmlDocCommand extends Command
{
    private const PATH_SCHEMA = __DIR__ . '/../../schema';
    private const PATH_OUTPUT = __DIR__ . '/../../build/html-doc';

    protected function configure(): void
    {
        $this->setName('generate:html-doc')
            ->setDescription('HTML Doc generation.')
            ->setHelp('This command allows you to parse schema and generate HTML Doc.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the Schema parsing...');

        $service = new SchemaService(self::PATH_SCHEMA);

        $service->parseSchema();

        $schema = $service->getSchema();

        $generator = new DocGeneratorService($schema, self::PATH_OUTPUT);

        $generator->generate();

        $output->writeln([
            'Schema has been parsed successfully.',
            'Number of tables: ' . count($schema->getTables()),
            'Number of codelists: ' . count($schema->getCodelists()),
        ]);
    }
}
