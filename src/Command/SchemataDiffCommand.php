<?php

namespace LinkORB\Schemata\Command;

use LinkORB\Schemata\Entity\Schema;
use LinkORB\Schemata\Service\DiffService;
use LinkORB\Schemata\Service\SchemaProviderPath;
use LinkORB\Schemata\Service\SchemaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchemataDiffCommand extends Command
{
    private const ARGUMENT_FIRST_SCHEMA_PATH  = 'firstSchemaPath';
    private const ARGUMENT_SECOND_SCHEMA_PATH = 'secondSchemaPath';

    protected function configure(): void
    {
        $this->setName('schemata:diff')
            ->setDescription('Schema Diff.')
            ->setHelp('This command allows you to compare two schemas.')
            ->addArgument(
                self::ARGUMENT_FIRST_SCHEMA_PATH,
                InputArgument::REQUIRED,
                'First Schema Directory Path'
            )
            ->addArgument(
                self::ARGUMENT_SECOND_SCHEMA_PATH,
                InputArgument::REQUIRED,
                'Second Schema Directory Path'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the Schema comparing...' . PHP_EOL);

        $schemaOne = $this->getCleanedSchemaByPath($input->getArgument(self::ARGUMENT_FIRST_SCHEMA_PATH));
        $schemaTwo = $this->getCleanedSchemaByPath($input->getArgument(self::ARGUMENT_SECOND_SCHEMA_PATH));

        $service = new DiffService();
        $diffLines = $service->calculateDiff($schemaOne, $schemaTwo);

        if (0 < count($diffLines)) {
            $output->writeln([
                'Legend:',
                '   "T" - Type Changed',
                '   "M" - Modified',
                '   "+" - Created',
                '   "-" - Deleted',
                '   "=" - Unchanged',
                PHP_EOL,
            ]);

            $output->writeln($diffLines);
        }
    }

    private function getCleanedSchemaByPath($path): Schema
    {
        $schemaProvider = new SchemaProviderPath($path);

        $service = new SchemaService($schemaProvider->getSchema());

        $service->parseSchema();

        $schema = $service->getSchema();
        $schema->cleanUpForDiff();

        return $schema;
    }
}
