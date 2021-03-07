<?php

namespace Spartan\Db\Command\Propel;

use Spartan\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Diff Propel
 *
 * @property string $dir
 *
 * @package Spartan\Db
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Diff extends Command
{
    protected function configure()
    {
        $this->withSynopsis('propel:diff', 'Run diff for Propel2')
             ->withOption('dir', 'Config dir', 'config')
             ->withOption('sql', 'Create sql file')
             ->withOption('conf', 'Create conf file');
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
        self::loadEnv();

        $propelDir = getcwd() . '/' . $this->dir;

        $cwd    = getcwd();
        $app    = getenv('APP_NAME');
        $dbName = getenv('DB_NAME');

        passthru("cd {$propelDir} && ../vendor/bin/propel reverse {$dbName}");
        passthru("cd {$propelDir} && php propel_schema.php");
        passthru("cd {$propelDir} && ../vendor/bin/propel model:build");

        if ($this->isOptionPresent('sql')) {
            passthru("cd {$propelDir} && ../vendor/bin/propel sql:build --overwrite");
        }

        if ($this->isOptionPresent('conf')) {
            passthru("cd {$propelDir} && ../vendor/bin/propel config:convert");
        }

        if (!file_exists("{$cwd}/src/Domain/Model/")) {
            mkdir("{$cwd}/src/Domain/Model/", 0777, true);
        }

        passthru("rm -Rf {$cwd}/src/Domain/Model/Base");
        passthru("rm -Rf {$cwd}/src/Domain/Model/Map");
        passthru("mv {$propelDir}/generated-classes/{$app}/Domain/Model/Base  {$cwd}/src/Domain/Model/Base");
        passthru("mv {$propelDir}/generated-classes/{$app}/Domain/Model/Map   {$cwd}/src/Domain/Model/Map");
        passthru("cp -n {$propelDir}/generated-classes/{$app}/Domain/Model/*.php {$cwd}/src/Domain/Model/");

        // cleanup
        passthru("rm -Rf {$propelDir}/generated-classes");
        passthru("rm -Rf {$propelDir}/generated-reversed-database");

        return 0;
    }
}
