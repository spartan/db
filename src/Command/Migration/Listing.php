<?php

namespace Spartan\Db\Command\Migration;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Listing Command
 *
 * @property int $limit
 *
 * @package Spartan\Db\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Listing extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:list', 'Show migrations')
             ->withOption('config', 'Path to config', './config/.env')
             ->withOption('limit', 'How many to show. Default 20', 20);
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

        $appliedMigrations  = $adapter->appliedMigrations($migration->table());
        $existingMigrations = $migration->migrations();
        rsort($existingMigrations);
        $existingMigrations = array_slice($existingMigrations, 0, $this->limit);

        $rows = [];
        foreach ($existingMigrations as $filename) {
            $rows[] = [
                $filename,
                in_array($filename, $appliedMigrations)
                    ? '<success>[x]</success>'
                    : '<danger>[-]</danger>',
            ];
        }

        $this->table(
            ['Migration', 'Status'],
            $rows
        )->render();

        return 0;
    }
}
