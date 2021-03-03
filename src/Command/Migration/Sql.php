<?php

namespace Spartan\Migration\Command;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sql Command
 *
 * @package Spartan\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Sql extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:sql', 'Generate SQL for current db')
             ->withOption('dir', 'Path to save into', '/dump')
             ->withOption('file', 'Path to config', './config/.env');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $envFile = $input->getOption('file');
        $dirPath = $input->getOption('dir');

        // using mysql for now
        $migration = new Migration($envFile);
        $adapter   = $migration->adapter();

        if (!file_exists($migration->dir() . $dirPath)) {
            mkdir($migration->dir() . $dirPath, 0777, true);
        }
        $config = $migration->config();
        $name   = $config['APP_SLUG'] ?? $config['APP_NAME'];

        // generate schema
        $time = $migration->time();
        $sql  = $adapter->generateSql();
        file_put_contents(
            str_replace('//', '/', $migration->dir() . "/{$dirPath}/{$time}_{$name}.sql"),
            $sql
        );

        return 0;
    }
}
