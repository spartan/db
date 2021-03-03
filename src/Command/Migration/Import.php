<?php

namespace Spartan\Migration\Command;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import Command
 *
 * @package Spartan\Migration
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
        $envFile   = $input->getOption('config');
        $migration = new Migration($envFile);
        $adapter   = $migration->adapter();

        $sql = file_get_contents($input->getArgument('file'));

        $adapter->exec($sql);

        return 0;
    }
}
