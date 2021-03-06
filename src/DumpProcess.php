<?php

namespace Worksome\Foggy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOMySql\Driver as PDOMySqlDriver;
use Doctrine\DBAL\DriverManager;
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
     * @throws DBALException
     * @throws JsonException
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
                'driverClass' => PDOMySqlDriver::class,
            ]);
        }

        $this->consoleOutput = $consoleOutput ?? new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, true);
    }

    /**
     * The method used to run the process.
     *
     * @throws DBALException
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

        $tables = $db->query($platform->getListTablesSQL());

        while ($tableName = $tables->fetchColumn(0)) {
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
