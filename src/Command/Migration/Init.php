<?php

namespace Spartan\Migration\Command;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Init Command
 *
 * @package Spartan\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Init extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:init', 'Init migration configuration file')
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

        $env                          = [];
        $env['MIGRATIONS_ENV_PREFIX'] = $this->inquire('Environment variables prefix (DB_REMOTE_,DB_): ') ?: 'DB_REMOTE_,DB_';
        $env['MIGRATIONS_DB_TABLE']   = $this->inquire('Migration table name (spartan_migration): ') ?: 'spartan_migration';
        $env['MIGRATIONS_DDL_TABLES'] = $this->inquire('Tables to watch DDL changes on (all by default, comma separated): ') ?: '';
        $env['MIGRATIONS_DML_TABLES'] = $this->inquire('Tables to watch DML/contents on (none by default, comma separated): ') ?: '';
        $env['MIGRATIONS_DIR']        = $this->inquire('Migrations dir (./data/migrations): ') ?: './data/migrations';
        $env['MIGRATIONS_TIMESTAMP']  = $this->choose('Timestamp format: ', ['local', 'utc'], 'local');

        $envContents = file_get_contents($envFile);
        $envContents .= PHP_EOL;
        foreach ($env as $k => $v) {
            $envContents .= "{$k}={$v}" . PHP_EOL;
        }
        file_put_contents($envFile, $envContents);

        // using mysql for now
        $migration = new Migration($envFile);
        $adapter   = $migration->adapter();

        $table = preg_replace('/[^a-zA-Z_]/', '', $migration->table());
        $adapter->createMigrationsTable($table);

        // create directory
        $this->process('mkdir -p ' . $migration->dir() . '/schema');

        // generate schema
        $time  = $migration->time();
        $array = $adapter->generate();
        file_put_contents(
            $migration->dir() . "/schema/{$time}_init.json",
            json_encode($array, JSON_PRETTY_PRINT)
        );

        $this->call('migration:sql', ['--dir' => '']);

        return 0;
    }
}
