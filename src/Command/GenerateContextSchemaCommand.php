<?php

namespace LinkORB\Schemata\Command;

use LinkORB\Schemata\Service\SchemaProviderPath;
use LinkORB\Schemata\Service\SchemaService;
use LinkORB\Schemata\Service\YamlGeneratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateContextSchemaCommand extends Command
{
    private const ARGUMENT_INPUT_PATH  = 'inputPath';
    private const ARGUMENT_OUTPUT_PATH = 'outputPath';

    private const OPTION_BUNDLE = 'bundle';

    protected function configure(): void
    {
        $this->setName('generate:context-schema')
            ->setDescription('Context Schema Generation.')
            ->setHelp('This command allows you to parse the schema and generate context yaml.')
            ->addArgument(
                self::ARGUMENT_INPUT_PATH,
                InputArgument::REQUIRED,
                'Schema Directory Path'
            )
            ->addArgument(
                self::ARGUMENT_OUTPUT_PATH,
                InputArgument::REQUIRED,
                'Context Output Path'
            )
            ->addOption(self::OPTION_BUNDLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the Schema parsing...');

        $schemaProvider = new SchemaProviderPath(
            $input->getArgument(self::ARGUMENT_INPUT_PATH)
        );

        $service = new SchemaService(
            $schemaProvider->getSchema(),
            SchemaService::CODELISTS_AS_TABLES
        );

        $service->parseSchema();

        $schema = $service->getSchema();

        $generator = new YamlGeneratorService(
            $schema,
            $input->getArgument(self::ARGUMENT_OUTPUT_PATH)
        );

        $generator->generate($input->getOption(self::OPTION_BUNDLE));

        $output->writeln([
            'Schema has been parsed successfully.',
            'Number of tables: ' . count($schema->getTables()),
            'Number of codelists: ' . count($schema->getCodelists()),
        ]);
    }
}
