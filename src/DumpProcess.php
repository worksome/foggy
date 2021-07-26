<?php

namespace Worksome\Foggy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DbalException;
use Safe\Exceptions\JsonException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Worksome\Foggy\Settings\Settings;
use function Safe\json_decode;

/**
 * The process used to handle creating a dump.
 *
 * This class is the one which runs the method in Dumper, selects the database to use and the tables.
 */
class DumpProcess
{
    private Settings $config;

    private Connection $db;

    private OutputInterface $dumpOutput;

    private ConsoleOutput $consoleOutput;

    /**
     * @param string|Connection  $dsn
     * @throws JsonException|DbalException
     */
    public function __construct($dsn, string $config, OutputInterface $dumpOutput, ConsoleOutput $consoleOutput = null)
    {
        $this->dumpOutput = $dumpOutput;
        $this->config = new Settings(json_decode(file_get_contents($config)));

        if ($dsn instanceof Connection) {
            $this->db = $dsn;
        } else {
            $dsn = preg_replace('_^mysqli:_', 'mysql:', $dsn);
            $this->db = DriverManager::getConnection([
                'url'         => $dsn,
                'charset'     => 'utf8',
            ]);
        }

        $this->consoleOutput = $consoleOutput ?? new ConsoleOutput(OutputInterface::VERBOSITY_NORMAL, true);
    }

    /**
     * The method used to run the process.
     * @throws DbalException
     */
    public function run(): void
    {
        $dumper = new Dumper(
            $this->dumpOutput,
            $this->consoleOutput
        );
        $dumper->dumpConfiguration();

        $db = $this->db;

        $platform = $db->getDatabasePlatform();

        $tables = $db->executeQuery($platform->getListTablesSQL());

        while ($tableName = $tables->fetchOne()) {
            $table = $this->config->findTable($tableName);

            // Skip table if not set in config.
            if ($table === null) {
                continue;
            }

            // Dump the schema of the table.
            $dumper->dumpSchema($tableName, $db);

            // Dump data for the table if allowed
            if ($table->withData()) {
                $dumper->dumpData($tableName, $table, $db);
            }
        }

        $dumper->dumpResetConfiguration();
    }
}
