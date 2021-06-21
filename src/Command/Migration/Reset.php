<?php

namespace Spartan\Db\Command\Migration;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reset Command
 *
 * @package Spartan\Db\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Reset extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:reset', 'Reset DB using sql migration files')
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
        $adapter   = $migration->adapter();
        $path      = $migration->dir();

        $files = glob("{$path}/*.sql");
        foreach ($files as $file) {
            if (substr($file, -4) == '.sql') {
                $output->writeln('Importing ' . $file . '...');
                $sql = explode('--@UNDO', file_get_contents($file))[0];
                $adapter->exec(trim($sql));
                $adapter->apply($migration->table(), substr($file, strrpos($file, '/') + 1));
            }
        }

        return 0;
    }
}
