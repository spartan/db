<?php

namespace Spartan\Db\Command\Seeder;

use Spartan\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Seed Command
 *
 * @package Spartan\Db
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Seed extends Command
{
    protected function configure()
    {
        $this->withSynopsis('seed:db', 'Run a seeder')->withArgument('name', 'Seeder name');
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
        $this->loadEnv();

        $className = getenv('APP_NAME') . '\\Domain\\Seeder\\' . ucfirst($input->getArgument('name'));

        (new $className())();

        return 0;
    }
}
