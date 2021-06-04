<?php

namespace Spartan\Db\Command\Migration;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Undo Command
 *
 * @package Spartan\Db\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Undo extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:undo', 'Undo an applied migration. Use carefully!')
             ->withOption('config', 'Path to config', './config/.env');
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
        $adapter   = $migration->adapter();

        $appliedMigrations = $adapter->appliedMigrations($migration->table());
        rsort($appliedMigrations);

        $filename = $this->choose('Choose migration', $appliedMigrations);

        $this->warning("The following sql will be undo:\n");
        $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[1];
        $output->write(trim($sql) . "\n");

        $confirm = $this->confirm('Confirm?');

        if ($confirm) {
            $this->note("Undo {$filename}...");
            $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[1];
            $adapter->exec(trim($sql));
            $adapter->undo($migration->table(), $filename);
        } else {
            $this->note('Cancelled!');
        }

        return 0;
    }
}
