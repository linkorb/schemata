<?php

namespace LinkORB\Schemata\Command;

use LinkORB\Schemata\Entity\Schema;
use LinkORB\Schemata\Service\SchemaSQLParserService;
use LinkORB\Schemata\Service\XMLGeneratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchemataParseSQLCommand extends Command
{
    private const ARGUMENT_SQL_PATH = 'SQLPath';
    private const ARGUMENT_XML_PATH = 'XMLPath';

    protected function configure(): void
    {
        $this->setName('schemata:parse-sql')
            ->setDescription('Schema SQL Parser.')
            ->setHelp('This command allows you to parse Schema SQL and convert it to XML format.')
            ->addArgument(
                self::ARGUMENT_SQL_PATH,
                InputArgument::REQUIRED,
                'Schema SQL Input Directory Path'
            )
            ->addArgument(
                self::ARGUMENT_XML_PATH,
                InputArgument::REQUIRED,
                'Schema XML Output Directory Path'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the Schema SQL parsing...' . PHP_EOL);

        $service = new SchemaSQLParserService($input->getArgument(self::ARGUMENT_SQL_PATH));

        $progressBar = new ProgressBar($output);

        $tables = $service->parse($progressBar);
        $progressBar->finish();

        $generator = new XMLGeneratorService(
            new Schema(),
            $input->getArgument(self::ARGUMENT_XML_PATH)
        );

        $generator->generateXML($tables);

        $output->writeln([
            PHP_EOL . PHP_EOL,
            'Done.',
        ]);
    }
}
