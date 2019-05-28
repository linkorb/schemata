<?php

namespace LinkORB\Schema\Command;

use LinkORB\Schema\Service\GraphQLGeneratorService;
use LinkORB\Schema\Service\SchemaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateGraphQLSchemaCommand extends Command
{
    private const PATH_SCHEMA = __DIR__ . '/../../schema';
    private const PATH_OUTPUT = __DIR__ . '/../../build/graphql';

    private const OPTION_BUNDLE = 'bundle';

    protected function configure(): void
    {
        $this->setName('generate:graphql-schema')
            ->setDescription('GraphQL Schema Generation.')
            ->setHelp('This command allows you to parse the schema and generate GraphQL schema.')
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

        $generator = new GraphQLGeneratorService($schema, self::PATH_OUTPUT);

        $generator->generate($input->getOption(self::OPTION_BUNDLE));

        $output->writeln([
            'Schema has been parsed successfully.',
            'Number of tables: ' . count($schema->getTables()),
            'Number of codelists: ' . count($schema->getCodelists()),
        ]);
    }
}
