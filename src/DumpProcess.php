<?php

namespace Worksome\Foggy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Tools\DsnParser;
use Safe\Exceptions\JsonException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Worksome\Foggy\Settings\Settings;
use function Safe\file_get_contents;
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
     * @param string|Connection $dsn
     *
     * @throws JsonException|DbalException
     */
    public function __construct(
        $dsn,
        string $config,
        OutputInterface $dumpOutput,
        ConsoleOutput|null $consoleOutput = null,
    ) {
        $this->dumpOutput = $dumpOutput;
        $this->config = new Settings(json_decode(file_get_contents($config)));

        if ($dsn instanceof Connection) {
            $this->db = $dsn;
        } else {
            $dsn = preg_replace('_^mysqli:_', 'mysql:', $dsn);
            $params = new DsnParser([
                'mysql'  => 'pdo_mysql',
                'mysql2' => 'pdo_mysql',
            ])->parse($dsn);

            $this->db = DriverManager::getConnection([
                ...$params,
                'charset' => 'utf8',
            ]);
        }

        $this->consoleOutput = $consoleOutput ?? new ConsoleOutput(OutputInterface::VERBOSITY_NORMAL, true);
    }

    /**
     * The method used to run the process.
     *
     * @throws DbalException
     */
    public function run(): void
    {
        $dumper = new Dumper(
            $this->dumpOutput,
            $this->consoleOutput
        );
        $dumper->dumpConfiguration();

        $this->dumpTables($dumper);
        $this->dumpViews($dumper);

        $dumper->dumpResetConfiguration();
    }

    private function dumpTables(Dumper $dumper): void
    {
        $db = $this->db;

        // `introspectTableNames()` replaces this in DBAL 4.5, but is unavailable in DBAL 3.x.
        $tables = $db->createSchemaManager()->listTableNames();

        foreach ($tables as $tableName) {
            $table = $this->config->findTable($tableName);

            // Skip table if not set in config.
            if ($table === null) {
                continue;
            }

            // Dump the schema of the table.
            $dumper->dumpTableSchema($tableName, $db);

            // Dump data for the table if allowed
            if ($table->withData()) {
                $dumper->dumpData($tableName, $table, $db);
            }
        }
    }

    private function dumpViews(Dumper $dumper): void
    {
        // `introspectViews()` replaces this in DBAL 4.5, but is unavailable in DBAL 3.x.
        $views = $this->db->createSchemaManager()->listViews();

        // DBAL 3.x keys views by name, whereas DBAL 4.x returns a list, so the name is read from the view.
        foreach ($views as $view) {
            $viewName = $view->getName();

            if ($this->config->findView($viewName) === null) {
                continue;
            }

            $dumper->dumpViewSchema($viewName, $view->getSql());
        }
    }
}
