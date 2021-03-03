<?php

namespace Spartan\Migration\Command;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Diff Command
 *
 * @property string $name
 *
 * @package Spartan\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Diff extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:diff', 'Generate a migration SQL file')
             ->withArgument('name', 'Name your migration')
             ->withOption('config', 'Path to config', './config/.env')
             ->withOption('dry', 'Dry run. Only shows sql');
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
        $envFile   = $input->getOption('config');
        $migration = new Migration($envFile);
        $adapter = $migration->adapter();

        /*
         * First check if there are any migrations that have not been applied
         */
        $appliedMigrations  = $adapter->appliedMigrations($migration->table());
        $existingMigrations = $migration->migrations();

        $unAppliedMigrations = array_diff($existingMigrations, $appliedMigrations);

        if ($unAppliedMigrations) {
            $this->danger('The following migrations have to be applied before running diff:');

            foreach ($unAppliedMigrations as $filename) {
                $this->note($filename);
                $output->write(file_get_contents($migration->dir() . '/' . $filename));
            }

            $this->warning('Run `migration:up --all` to apply all migrations.');

            return 0;
        }

        $oldSchema = $migration->previousSchema();
        $newSchema = $adapter->generate();
        $diff      = $adapter->diff($newSchema, $oldSchema);

        $upSql   = trim($diff['up']);
        $downSql = trim($diff['down']);
        $time    = $migration->time();
        $name    = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $this->name));

        if (!($upSql || $downSql)) {
            $this->note('No changes found!');

            return 0;
        }

        if (!$this->isOptionPresent('dry')) {
            $this->note('Saving new schema...');

            file_put_contents(
                $migration->dir() . "/schema/{$time}_{$name}.json",
                json_encode($newSchema, JSON_PRETTY_PRINT)
            );

            $this->note('Saving new sql migrations...');

            file_put_contents(
                $migration->dir() . "/{$time}_{$name}.sql",
                sprintf(
                    "%s\n\n%s\n\n--@UNDO\n\n%s\n",
                    "-- {$name}",
                    $upSql,
                    $downSql
                )
            );

            $this->note('Saving to database...');

            $adapter->apply($migration->table(), "{$time}_{$name}.sql");
        }

        $this->note($migration->dir() . "/{$time}_{$name}.sql");
        $this->panel(
            sprintf(
                "%s\n\n%s\n\n--@UNDO\n\n%s\n",
                "-- {$name}",
                $upSql,
                $downSql
            )
        );

        return 0;
    }
}
