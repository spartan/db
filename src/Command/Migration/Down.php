<?php

namespace Spartan\Db\Command\Migration;

use Spartan\Console\Command;
use Spartan\Db\Migration\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Down Command
 *
 * @property int $limit
 *
 * @package Spartan\Db\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Down extends Command
{
    protected function configure()
    {
        $this->withSynopsis('migration:down', 'Undo migrations. One step at a time.')
             ->withOption('config', 'Path to config', './config/.env')
             ->withOption('dry', 'Dry run. Do not apply anything')
             ->withOption('limit', 'How many steps to undo. Default one.', 1);
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

        rsort($appliedMigrations);
        $toApply = array_slice($appliedMigrations, 0, $this->limit ?: 1);

        if ($this->isOptionPresent('dry')) {
            $this->warning("The following migrations will be applied:\n");
            foreach ($toApply as $filename) {
                $this->note($filename);
                $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[1];
                $output->write(trim($sql) . "\n");
            }
        } else {
            foreach ($toApply as $filename) {
                $this->note("Undo {$filename}...");
                $sql = explode('--@UNDO', file_get_contents($migration->dir() . '/' . $filename))[1];
                $adapter->exec(trim($sql));
                $adapter->undo($migration->table(), $filename);
            }
        }

        return 0;
    }
}
