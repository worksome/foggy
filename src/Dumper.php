<?php

namespace Worksome\Foggy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOConnection;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Worksome\Foggy\Settings\Table;

/**
 * Class Dumper.
 */
class Dumper
{
    protected OutputInterface $dumpOutput;

    protected OutputInterface $consoleOutput;

    protected int $bufferSize = 10485760;

    public function __construct(OutputInterface $dumpOutput, OutputInterface $consoleOutput)
    {
        $this->dumpOutput = $dumpOutput;
        $this->consoleOutput = $consoleOutput;
    }

    public function dumpLine(string $message, bool $newLine = false): void
    {
        $this->dumpOutput->write($message, $newLine, OutputInterface::OUTPUT_RAW);
    }

    /**
     * Writes a new line to the specified dump output interface.
     */
    public function dumpNewLine(string $message = ''): void
    {
        $this->dumpLine($message, true);
    }

    /**
     * Creates a instance of a progress bar in the specified console output interface.
     */
    public function createProgressBar(int $max = 1): ProgressBar
    {
        $progress = new ProgressBar($this->consoleOutput, $max);
        $progress->setOverwrite(true);
        $progress->setRedrawFrequency(1);

        return $progress;
    }

    /**
     * Dumps all the variables which needs to be set for the dump to work.
     *
     * Most of the variables are taken from a default mysqldump script.
     */
    public function dumpConfiguration(): void
    {
        $this->dumpNewLine('/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;');
        $this->dumpNewLine('/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;');
        $this->dumpNewLine('/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;');
        $this->dumpNewLine('SET NAMES utf8mb4 ;');
        $this->dumpNewLine('/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;');
        $this->dumpNewLine("/*!40103 SET TIME_ZONE='+00:00' */;");
        $this->dumpNewLine('/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;');
        $this->dumpNewLine('/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;');
        $this->dumpNewLine("/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;");
        $this->dumpNewLine('/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;');
    }

    /**
     * Dumps all the lines needed for resetting the variables, so we don't leave any
     * weird settings in the connection.
     *
     * This command requires you to run dumpConfiguration first.
     * Most of the variables are taken from a default mysqldump script.
     *
     * @see Dumper::dumpConfiguration()
     */
    public function dumpResetConfiguration(): void
    {
        $this->dumpNewLine('/*!40101 SET character_set_client = @saved_cs_client */;');
        $this->dumpNewLine('/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;');
        $this->dumpNewLine('/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;');
        $this->dumpNewLine('/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;');
        $this->dumpNewLine('/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;');
        $this->dumpNewLine('/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;');
        $this->dumpNewLine('/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;');
        $this->dumpNewLine('/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;');
        $this->dumpNewLine('/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;');
    }

    /**
     * Dumps the schema definition of the table.
     *
     * @throws DBALException
     */
    public function dumpSchema(string $table, Connection $db): void
    {
        $this->keepalive($db);
        $this->dumpNewLine("-- BEGIN STRUCTURE `$table`");
        $this->dumpNewLine("DROP TABLE IF EXISTS `$table`;");
        $this->dumpNewLine('/*!40101 SET @saved_cs_client     = @@character_set_client */;');
        $this->dumpNewLine('SET character_set_client = utf8mb4;');

        $tableCreationCommand = $db->fetchColumn("SHOW CREATE TABLE `$table`", [], 1);

        $this->dumpNewLine($tableCreationCommand . ';');
        $this->dumpNewLine();

        $progress = $this->createProgressBar(1);
        $progress->setFormat("Dumping schema <fg=cyan>$table</>: <fg=yellow>%percent:3s%%</>");
        $progress->setOverwrite(true);
        $progress->setRedrawFrequency(1);
        $progress->start();
        $progress->setFormat("Dumping schema <fg=green>$table</>: <fg=green>%percent:3s%%</> Took: %elapsed%");
        $progress->finish();
        if ($this->consoleOutput instanceof ConsoleOutput) {
            $this->consoleOutput->getErrorOutput()->writeln(''); // write a newline after the progressbar.
        }
    }

    /**
     * Dumps the data for the specified table based on the settings for the Table.
     *
     * @throws DBALException
     */
    public function dumpData(string $table, Table $tableSettings, Connection $db): void
    {
        $this->keepalive($db);
        $cols = $this->getColumnsForTable($table, $db);

        $selectQuery = 'SELECT ';
        $first = true;
        foreach (array_keys($cols) as $name) {
            if (!$first) {
                $selectQuery .= ', ';
            }

            $selectQuery .= "`$name`";
            $first = false;
        }
        $selectQuery .= " FROM `$table`";

        $selectQuery .= $tableSettings->getWhere();

        $this->dumpNewLine("-- BEGIN DATA $table");

        $bufferSize = 0;
        $max = $this->bufferSize;
        $numRows = $db->fetchColumn("SELECT COUNT(*) FROM `{$table}` {$tableSettings->getWhere()}");

        // If no data, just exist.
        if ($numRows == 0) {
            return;
        }

        $progress = $this->createProgressBar($numRows);
        $progress->setFormat("Dumping data <fg=cyan>$table</>: <fg=yellow>%percent:3s%%</> %remaining% / %estimated%");
        $progress->setRedrawFrequency(max($numRows / 100, 1));
        $progress->start();

        /** @var PDOConnection $wrappedConnection */
        $wrappedConnection = $db->getWrappedConnection();
        $wrappedConnection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        foreach ($db->query($selectQuery) as $row) {
            $b = $this->rowLengthEstimate($row);

            // Start a new statement to ensure that the line does not get too long.
            if ($bufferSize && $bufferSize + $b > $max) {
                $this->dumpNewLine(';');
                $bufferSize = 0;
            }

            if ($bufferSize == 0) {
                $this->dumpLine($this->insertValuesStatement($table, $cols));
            } else {
                $this->dumpLine(',');
            }

            $firstCol = true;
            $this->dumpNewLine('');
            $this->dumpLine('(');

            foreach ($row as $name => $value) {
                if (!$firstCol) {
                    $this->dumpLine(', ');
                }

                $this->dumpLine($tableSettings->getStringForInsertStatement($name, $value, $db, $row));
                $firstCol = false;
            }
            $this->dumpLine(')');
            $bufferSize += $b;
            $progress->advance();
        }
        $progress->setFormat("Dumping data <fg=green>$table</>: <fg=green>%percent:3s%%</> Took: %elapsed%");
        $progress->finish();
        if ($this->consoleOutput instanceof ConsoleOutput) {
            $this->consoleOutput->getErrorOutput()->write("\n"); // write a newline after the progressbar.
        }

        $wrappedConnection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        if ($bufferSize) {
            $this->dumpNewLine(';');
        }

        $this->dumpNewLine();
    }

    /**
     * Returns all columns for a table.
     */
    protected function getColumnsForTable(string $table, Connection $db): array
    {
        $columns = [];
        foreach ($db->fetchAll("SHOW COLUMNS FROM `$table`") as $row) {
            $columns[$row['Field']] = $row['Type'];
        }

        return $columns;
    }

    /**
     * @param array(string=>mixed) $cols
     */
    protected function insertValuesStatement($table, $cols): string
    {
        return "INSERT INTO `$table` (`" . implode('`, `', array_keys($cols)) . '`) VALUES ';
    }

    protected function rowLengthEstimate(array $row): int
    {
        $l = 0;
        foreach ($row as $value) {
            $l += strlen($value);
        }

        return $l;
    }

    private function keepalive(Connection $db): void
    {
        if (false === $db->ping()) {
            $db->close();
            $db->connect();
        }
    }
}
