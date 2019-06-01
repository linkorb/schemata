<?php

namespace LinkORB\Schemata\Command;

use LinkORB\Schemata\Service\SchemaProviderPath;
use LinkORB\Schemata\Service\SchemaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchemataValidateCommand extends Command
{
    private const ARGUMENT_INPUT_PATH = 'inputPath';

    protected function configure(): void
    {
        $this->setName('schemata:validate')
            ->setDescription('Schema Validation.')
            ->setHelp('This command allows you to validate schema against any issues.')
            ->addArgument(
                self::ARGUMENT_INPUT_PATH,
                InputArgument::REQUIRED,
                'Schema Directory Path'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the Schema parsing...');

        $schemaProvider = new SchemaProviderPath($input->getArgument(self::ARGUMENT_INPUT_PATH));

        $service = new SchemaService($schemaProvider->getSchema());

        $service->parseSchema();

        $schema = $service->getSchema();

        if (!empty($schema->getTablesWithIssues())) {
            $output->writeln([
                'There are some validation issues.',
            ]);

            exit(-1);
        }

        $output->writeln([
            "There aren't any validation issues",
        ]);
    }
}
