<?php

namespace Spartan\Db\Command\Migration;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Up Command
 *
 * @property int $limit
 *
 * @package Spartan\Db\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Up extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:up', 'Apply migrations. One step at a time.')
            ->withOption('config', 'Path to config', './config/.env')
             ->withOption('dry', 'Dry run. Do not apply anything')
             ->withOption('all', 'Apply all migrations')
             ->withOption('limit', 'How many steps to apply. Default one.', 1);
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
        $adapter   = $migration->adapter();

        $appliedMigrations  = $adapter->appliedMigrations($migration->table());
        $existingMigrations = $migration->migrations();

        $unAppliedMigrations = array_diff($existingMigrations, $appliedMigrations);
        sort($unAppliedMigrations);

        if (!$unAppliedMigrations) {
            $this->note('No migrations to apply!');

            return 0;
        }

        if (!$this->isOptionPresent('all')) {
            $unAppliedMigrations = array_slice($unAppliedMigrations, 0, $this->limit ?: 1);
        }


        if ($this->isOptionPresent('dry')) {
            $this->warning("The following migrations will be applied:\n");
            foreach ($unAppliedMigrations as $filename) {
                $this->note($filename);
                $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[0];
                $output->write(trim($sql) . "\n");
            }
        } else {
            foreach ($unAppliedMigrations as $filename) {
                $this->note("Apply {$filename}...");
                $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[0];
                $adapter->exec(trim($sql));
                $adapter->apply($migration->table(), $filename);
            }
        }

        return 0;
    }
}
