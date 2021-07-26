<?php

namespace Worksome\Foggy;

use Safe\Exceptions\JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A symfony command for running the package.
 *
 * This class is used to run the package in the bin file.
 */
class FoggyCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('foggy:dump')
            ->setDescription('Dumps a database based on rules')
            ->addArgument(
                'dsn',
                InputArgument::REQUIRED,
                'The Database-DSN to connect to.'
            )
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'The config file to filter dump by'
            );
    }

    /**
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dsn = $input->getArgument('dsn');
        $configFile = $input->getArgument('config');

        $process = new DumpProcess($dsn, $configFile, $output);
        $process->run();
        return 0;
    }
}
