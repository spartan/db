<?php

namespace Spartan\Db\Command\Seeder\Make;

use Spartan\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Make Command
 *
 * @package Spartan\Db
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Make extends Command
{
    protected function configure()
    {
        $this->withSynopsis('seeder:make', 'Create a seeder for database')
             ->withArgument('name', 'Seeder name');
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

        $appName = getenv('APP_NAME');

        $namespaceName = $appName . '\\Domain\\Seeder';
        $className     = $input->getArgument('name');

        $dir = "./src/Domain/Seeder";
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }

        file_put_contents(
            "./src/Domain/Seeder/{$className}.php",
            str_replace(
                ['NamespaceName', 'ClassName'],
                [$namespaceName, $className],
                trim($this->seederTemplate()) . PHP_EOL
            )
        );

        return 0;
    }

    public function seederTemplate()
    {
        return <<<EOT
<?php

namespace NamespaceName;

use Spartan\Db\Definition\SeederTrait;

class ClassName extends SeederAbstract
{
    use SeederTrait;

    public function __invoke()
    {
        /*
         * Example for Propel
         */

        // \Spartan\Db\Adapter\Propel\Propel2::connect();
        // \$con = \Propel\Runtime\Propel::getServiceContainer()->getWriteConnection(getenv('APP_SLUG'));
        // \$con->exec('START TRANSACTION');
        // \$con->exec('set foreign_key_checks=0');
        // \$con->exec("TRUNCATE TABLE \$this->options['table']");
        // \$con->exec('set foreign_key_checks=1');
        // \$con->exec('COMMIT');
        // \Spartan\Db\Adapter\Propel\Propel2::disconnect();
    }
}
EOT;
    }
}
