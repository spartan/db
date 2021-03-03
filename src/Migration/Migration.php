<?php

namespace Spartan\Db\Migration;

use Spartan\Db\Migration\Engine\Mysql;

/**
 * Migration Migration
 *
 * @package Spartan\Migration
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Migration
{
    protected string $envFile;

    public function __construct(string $envFile = './config/.env')
    {
        $this->envFile = $envFile;
    }

    /*
     *
     * Connection
     */

    /**
     * @return Mysql
     */
    public function adapter()
    {
        $con = $this->connectionConfig();

        if ($con['adapter'] != 'mysql') {
            throw new \InvalidArgumentException('Adapter not supported: ' . $con['adapter']);
        }

        $adapter = '\\Spartan\\Migration\\Schema\\' . ucfirst($con['adapter']);

        return new $adapter($this->pdo());
    }

    /**
     * @return array
     */
    public function connectionConfig(): array
    {
        if (!file_exists($this->envFile)) {
            throw new \LogicException('The migrations configuration file could not be found!');
        }

        // parse migration env
        $cfg = parse_ini_file($this->envFile, false, INI_SCANNER_RAW) ?: [];

        // parse project env
        $env      = parse_ini_file($this->envFile, false, INI_SCANNER_RAW);
        $prefixes = explode(',', $cfg['MIGRATIONS_ENV_PREFIX']) ?: ['DB_'];

        // db connection data
        $con = [];
        foreach (['adapter', 'name', 'user', 'username', 'pass', 'password', 'host', 'port'] as $suffix) {
            foreach ($prefixes as $prefix) {
                if (isset($con[$suffix])) {
                    continue;
                }

                $con[$suffix] = $env[strtoupper("{$prefix}{$suffix}")] ?? null;
            }
        }

        return $con;
    }

    /**
     * @return \PDO
     */
    public function pdo()
    {
        $con = $this->connectionConfig();

        return new \PDO(
            "{$con['adapter']}:host={$con['host']};dbname={$con['name']};port={$con['port']};charset=utf8",
            $con['username'] ?: $con['user'],
            $con['password'] ?: $con['pass'],
        );
    }

    /*
     *
     * Helpers
     */

    /**
     * @return array|false
     */
    public function migrations()
    {
        $dir = $this->dir();

        $files = glob("{$dir}/*.sql");

        foreach ($files as &$filename) {
            $filename = basename($filename);
        }

        return $files;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function time()
    {
        $mode = $this->timestampMode();

        $dt = new \DateTime();

        if ($mode == 'utc') {
            $dt->setTimezone(new \DateTimeZone('UTC'));
        }

        return $dt->format('ymd_His');
    }

    /**
     * @return mixed[]
     */
    public function config(): array
    {
        return parse_ini_file($this->envFile, false, INI_SCANNER_RAW) ?: [];
    }

    /**
     * @return array
     */
    public function previousSchema(): array
    {
        $schemaDir = $this->dir() . '/schema';

        $files = glob("$schemaDir/*.json");

        sort($files);

        $previousSchemaFile = array_pop($files);

        return json_decode(file_get_contents($previousSchemaFile), true);
    }

    /*
     *
     * ENV
     */

    /**
     * Example: local, utc
     *
     * @return string
     */
    public function timestampMode()
    {
        return (string)$this->config()['MIGRATIONS_TIMESTAMP'];
    }

    /**
     * @return string
     */
    public function dir(): string
    {
        return (string)$this->config()['MIGRATIONS_DIR'];
    }

    /**
     * @return string
     */
    public function table(): string
    {
        return (string)$this->config()['MIGRATIONS_DB_TABLE'];
    }

    /**
     * @return array
     */
    public function ddlTables(): array
    {
        return array_filter(explode(',', $this->config()['MIGRATIONS_DDL_TABLES']));
    }

    /**
     * @return array
     */
    public function dmlTables(): array
    {
        return array_filter(explode(',', $this->config()['MIGRATIONS_DML_TABLES']));
    }
}
