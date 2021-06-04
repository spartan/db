<?php

namespace Spartan\Db\Command\Migration;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import Command
 *
 * @package Spartan\Db\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Import extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:import', 'Import an SQL file')
             ->withArgument('file', 'Path to SQL file')
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

        $sql = file_get_contents($input->getArgument('file'));

        $output->writeln('Importing ' . $input->getArgument('file') . '...');
        $adapter->exec($sql, false);

        return 0;
    }
}
