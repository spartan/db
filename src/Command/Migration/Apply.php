<?php

namespace Spartan\Db\Command\Migration;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Apply Command
 *
 * @package Spartan\Db\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Apply extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:apply', 'Apply an un-applied migration. Use carefully!')
             ->withOption('config', 'Path to config');
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
        $migration = new Migration($input->getOption('config'));

        $adapter = $migration->adapter();

        $appliedMigrations   = $adapter->appliedMigrations($migration->table());
        $existingMigrations  = $migration->migrations();
        $unAppliedMigrations = array_diff($existingMigrations, $appliedMigrations);
        rsort($unAppliedMigrations);

        $filename = $this->choose('Choose migration', $unAppliedMigrations);

        $this->warning("The following sql will be applied:\n");
        $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[0];
        $output->write(trim($sql) . "\n");

        $confirm = $this->confirm('Confirm?');

        if ($confirm) {
            $this->note("Apply {$filename}...");
            $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[0];
            $adapter->exec(trim($sql));
            $adapter->apply($migration->table(), $filename);
        } else {
            $this->note('Cancelled!');
        }

        return 0;
    }
}
