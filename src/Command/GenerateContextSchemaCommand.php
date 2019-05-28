<?php

namespace LinkORB\Schema\Command;

use LinkORB\Schema\Service\SchemaService;
use LinkORB\Schema\Service\YamlGeneratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateContextSchemaCommand extends Command
{
    private const PATH_SCHEMA = __DIR__ . '/../../schema';
    private const PATH_OUTPUT = __DIR__ . '/../../build/context';

    private const OPTION_BUNDLE = 'bundle';

    protected function configure(): void
    {
        $this->setName('generate:context-schema')
            ->setDescription('Context Schema Generation.')
            ->setHelp('This command allows you to parse the schema and generate context yaml.')
            ->addOption(self::OPTION_BUNDLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the Schema parsing...');

        $service = new SchemaService(
            self::PATH_SCHEMA,
            SchemaService::CODELISTS_AS_TABLES
        );

        $service->parseSchema();

        $schema = $service->getSchema();

        $generator = new YamlGeneratorService($schema, self::PATH_OUTPUT);

        $generator->generate($input->getOption(self::OPTION_BUNDLE));

        $output->writeln([
            'Schema has been parsed successfully.',
            'Number of tables: ' . count($schema->getTables()),
            'Number of codelists: ' . count($schema->getCodelists()),
        ]);
    }
}
