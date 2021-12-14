<?php

namespace Spartan\Db\Adapter\Propel;

use Exception;
use InvalidArgumentException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Propel\Runtime\Connection\ConnectionManagerMasterSlave;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Propel\Runtime\Util\Profiler;

/**
 * Propel2 Propel
 *
 * @package Spartan\Db
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Propel2
{
    /**
     * @param array $config
     *
     * @return ServiceContainerInterface
     * @throws Exception
     */
    public static function connect(array $config = [])
    {
        if (!$config) {
            $config = require './config/propel.php';
        }

        $name       = $config['propel']['runtime']['defaultConnection'];
        $connection = current($config['propel']['database']['connections']);

        /** @var StandardServiceContainer $serviceContainer */
        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->checkVersion(2);
        $serviceContainer->setAdapterClass($name, $connection['adapter']);

        /*
         * Manager
         */
        if ($connection['slaves'] ?? []) {
            $manager = new ConnectionManagerMasterSlave();
            $manager->setWriteConfiguration($connection);
            $manager->setReadConfiguration($connection['slaves']);
        } else {
            $manager = new ConnectionManagerSingle();
            $manager->setConfiguration($connection);
        }

        $manager->setName($name);
        $serviceContainer->setConnectionManager($name, $manager);
        $serviceContainer->setDefaultDatasource($name);

        /*
         * Logger
         */
        if (getenv('PROPEL_DEBUG') || getenv('PROPEL_PROFILE')) {
            $serviceContainer->setLogger('defaultLogger', self::defaultLogger($name));

            Propel::getWriteConnection($name)->setLogMethods(
                [
                    'exec',
                    'query',
                    'execute', // these first three are the default
                    'beginTransaction',
                    'commit',
                    'rollBack',
                    //'bindValue'
                ]
            );
        }

        /*
         * Profiler
         */
        if (getenv('PROPEL_PROFILE')) {
            $profiler = new Profiler();
            $profiler->setConfiguration($config['propel']['runtime']['profiler']);
            $profiler->start();
            $serviceContainer->setProfiler($profiler);
        }

        require_once './config/propel_map.php';

        return $serviceContainer;
    }

    /**
     * @param string $name
     *
     * @return Logger
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function defaultLogger(string $name)
    {
        $handler = new StreamHandler('./data/logs/propel_log');
        $handler->setFormatter(
            new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n\n"
            )
        );

        $logger = new Logger($name);
        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * Close connection
     */
    public static function disconnect()
    {
        Propel::getServiceContainer()->closeConnections();
    }

    /**
     * @param string|null $name
     * @param string      $mode
     */
    public static function transaction(string $name = null, string $mode = ServiceContainerInterface::CONNECTION_WRITE)
    {
        Propel::getServiceContainer()
              ->getConnection($name, $mode)
              ->beginTransaction();
    }

    /**
     * @param string|null $name
     * @param string      $mode
     */
    public static function commit(string $name = null, $mode = ServiceContainerInterface::CONNECTION_WRITE)
    {
        Propel::getServiceContainer()
              ->getConnection($name, $mode)
              ->commit();
    }

    /**
     * @param string|null $name
     * @param string      $mode
     */
    public static function rollback(string $name = null, $mode = ServiceContainerInterface::CONNECTION_WRITE)
    {
        Propel::getServiceContainer()
              ->getConnection($name, $mode)
              ->rollback();
    }

    /*
     * Helpers
     */

    /**
     * @param string $class      Model class name or table name
     * @param string $csv        CSV as string
     * @param array  $defaults   Default column values
     * @param string $fieldNames Field names format
     */
    public static function importCSV(
        string $class,
        string $csv,
        array $defaults = [],
        $fieldNames = TableMap::TYPE_FIELDNAME
    ) {
        /*
         * Expecting first row from CSV to have the column listings
         *
         * Example:
         * id,name
         * 1,test
         * 2,test2
         */

        // we got table name
        if (!class_exists($class)) {
            $class = getenv('APP_NAME') . '\\Domain\\Model\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $class)));
        }

        [$row1, $data] = explode("\n", trim($csv), 2);
        $columns = explode(',', $row1);

        foreach (explode("\n", $data) as $line) {
            $values = explode(',', $line);
            $model  = new $class();
            $model->fromArray(array_combine($columns, $values) + $defaults, $fieldNames);
            $model->save();
        }
    }

    /**
     * @param string $class      Model class name or table name
     * @param string $json       Json as string
     * @param array  $defaults   Default column values
     * @param string $fieldNames Field names format
     */
    public static function importJSON(
        string $class,
        string $json,
        array $defaults = [],
        $fieldNames = TableMap::TYPE_FIELDNAME
    ) {
        // we got table name
        if (!class_exists($class)) {
            $class = getenv('APP_NAME') . '\\Domain\\Model\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $class)));
        }

        $data = json_decode($json, true);

        foreach ($data as $datum) {
            $model = new $class();
            $model->fromArray($datum + $defaults, $fieldNames);
            $model->save();
        }
    }
}
