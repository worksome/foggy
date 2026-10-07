<?php

namespace Worksome\Foggy\Tests;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Worksome\Foggy\DumpProcess;

it('can dump an empty database on a non-MySQL connection', function () {
    $config = tempnam(sys_get_temp_dir(), 'foggy');
    file_put_contents($config, '{"database": {"*": {"withData": true}}}');

    $output = new BufferedOutput();

    $process = new DumpProcess(
        DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
        $config,
        $output,
        new ConsoleOutput(OutputInterface::VERBOSITY_QUIET),
    );

    $process->run();

    unlink($config);

    expect($output->fetch())
        ->toContain('SET NAMES utf8mb4 ;')
        ->toContain('/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;');
});
