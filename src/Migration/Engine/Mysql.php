<?php

namespace Spartan\Db\Migration\Engine;

class Mysql
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string $name
     */
    public function createMigrationsTable(string $name)
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$name}` (ts datetime NOT NULL, name varchar(100) NOT NULL, apply_at datetime NULL)");
    }

    /**
     * @param $table
     * @param $filename
     */
    public function apply($table, $filename)
    {
        $name = substr($filename, 14, -4);
        $ts   = substr($filename, 0, 2) . '-'
            . substr($filename, 2, 2) . '-'
            . substr($filename, 4, 2) . ' '
            . substr($filename, 7, 2) . ':'
            . substr($filename, 9, 2) . ':'
            . substr($filename, 11, 2);


        $stmt = $this->pdo->prepare("INSERT INTO `{$table}` VALUES (:ts, :name, now())");
        $stmt->execute(['ts' => $ts, 'name' => $name]);
    }

    /**
     * @param $table
     * @param $filename
     */
    public function undo($table, $filename)
    {
        $ts = substr($filename, 0, 2) . '-'
            . substr($filename, 2, 2) . '-'
            . substr($filename, 4, 2) . ' '
            . substr($filename, 7, 2) . ':'
            . substr($filename, 9, 2) . ':'
            . substr($filename, 11, 2);

        $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE ts = :ts");
        $stmt->execute(['ts' => $ts]);
    }

    /**
     * @param string $table
     *
     * @return array
     * @throws \Exception
     */
    public function appliedMigrations(string $table)
    {
        $applied = $this->pdo->query("SELECT * FROM `{$table}`", \PDO::FETCH_ASSOC)->fetchAll();
        foreach ($applied as &$data) {
            $data = (new \DateTime($data['ts']))->format('ymd_His') . '_' . $data['name'] . '.sql';
        }

        return $applied;
    }

    /**
     * @param $sql
     *
     * @throws \Exception
     */
    public function exec($sql, bool $skipFkChecks = true)
    {
        if ($skipFkChecks) {
            $sql = 'SET foreign_key_checks = 0;'
                . PHP_EOL . $sql . PHP_EOL
                . 'SET foreign_key_checks = 1;';
        }

        if (false === $this->pdo->exec($sql)) {
            throw new \Exception(json_encode($this->pdo->errorInfo()));
        }
    }

    /**
     * Generate a schema
     *
     * @return array
     */
    public function generate()
    {
        $tables = $this->pdo->query('SHOW TABLES', \PDO::FETCH_NUM)->fetchAll();
        foreach ($tables as $key => &$data) {
            $data = $data[0];
        }

        $schema = [];

        foreach ($tables as $index => $name) {
            $sql            = $this->pdo->query("SHOW CREATE TABLE `{$name}`", \PDO::FETCH_NUM)->fetchAll()[0][1];
            $schema[$index] = self::tableSqlToSchema($sql);
        }

        return $schema;
    }

    public function generateSql()
    {
        $tables = $this->pdo->query('SHOW TABLES', \PDO::FETCH_NUM)->fetchAll();
        foreach ($tables as $key => &$data) {
            $data = $data[0];
        }

        $sql = [
            "-- dump @ " . date('Y-m-d H:i:s'),
            "\n\n",
            'SET foreign_key_checks=0;',
            "\n\n"
        ];

        foreach ($tables as $index => $name) {
            $table = $this->pdo->query("SHOW CREATE TABLE `{$name}`", \PDO::FETCH_NUM)->fetchAll()[0][1];
            $sql[] = "DROP TABLE IF EXISTS `{$name}`;\n";
            $sql[] = "{$table};\n";
            $sql[] = "\n";
        }

        $sql[] = "\nSET foreign_key_checks=1;";

        return implode("", $sql);
    }

    /**
     * Generate a diff between 2 schemas
     * Supported auto-detect operations:
     *  - Table
     *      - create
     *      - drop
     *  - Column
     *      - create
     *      - alter
     *      - drop
     *      - rename (partial)
     *  - Index
     *      - create
     *      - drop
     *
     * Not supported auto-detect operations:
     * - rename table
     * - rename column (partial)
     * - rename index
     *
     * @param array $newSchema
     * @param array $oldSchema
     *
     * @return array
     */
    public function diff(array $newSchema, array $oldSchema): array
    {
        $upSql   = [];
        $downSql = [];

        $checkTables      = $this->checkTables($newSchema, $oldSchema);
        $checkColumns     = $this->checkColumns($newSchema, $oldSchema);
        $checkIndexes     = $this->checkIndexes($newSchema, $oldSchema);
        $checkConstraints = $this->checkConstraints($newSchema, $oldSchema);

        $upSql[] = $checkTables['up'];
        $upSql[] = $checkConstraints['up'];
        $upSql[] = $checkIndexes['up'];
        $upSql[] = $checkColumns['up'];

        $downSql[] = $checkTables['down'];
        $downSql[] = $checkConstraints['down'];
        $downSql[] = $checkIndexes['down'];
        $downSql[] = $checkColumns['down'];

        return [
            'up'   => implode("\n", $upSql),
            'down' => implode("\n", $downSql),
        ];
    }

    /**
     * @param array $newSchema
     * @param array $oldSchema
     *
     * @return array
     */
    public function checkTables(array $newSchema, array $oldSchema): array
    {
        $upSql   = [];
        $downSql = [];

        $oldSchema = self::indexedSchema($oldSchema);
        $newSchema = self::indexedSchema($newSchema);

        $createdTables = array_diff_key($newSchema, $oldSchema);
        foreach ($createdTables as $createdTableName => $tableSchema) {
            $upSql[]   = $this->tableCreateSql($createdTableName);
            $downSql[] = "DROP TABLE `{$createdTableName}`;";
        }

        $deletedTables = array_diff_key($oldSchema, $newSchema);
        foreach ($deletedTables as $deletedTableName => $tableSchema) {
            $upSql[]   = "DROP TABLE `{$deletedTableName}`;";
            $downSql[] = self::tableSchemaToSql($tableSchema);
        }

        return [
            'up'   => implode("\n", $upSql),
            'down' => implode("\n", $downSql),
        ];
    }

    /**
     * @param array $newSchema
     * @param array $oldSchema
     *
     * @return array
     */
    public function checkColumns(array $newSchema, array $oldSchema)
    {
        $upSql   = [];
        $downSql = [];

        $oldSchema = self::indexedSchema($oldSchema);
        $newSchema = self::indexedSchema($newSchema);

        foreach ($newSchema as $tableName => $newTableSchema) {
            $oldTableSchema = $oldSchema[$tableName] ?? null;
            if (!$oldTableSchema) {
                continue;
            }

            if ($newTableSchema['fields'] === $oldTableSchema['fields']) {
                continue;
            }

            /*
             * 0 => id
             * 1 => name
             */
            $newTableFields = array_keys($newTableSchema['fields']);
            $oldTableFields = array_keys($oldTableSchema['fields']);

            $createdColumns = array_diff_key($newTableSchema['fields'], $oldTableSchema['fields']);
            $deletedColumns = array_diff_key($oldTableSchema['fields'], $newTableSchema['fields']);

            if ($createdColumns) {
                foreach ($createdColumns as $createdColumnName => $createdColumnType) {
                    /*
                     * We need to determine if it's a rename
                     * Check for deleted ones with the same type/signature
                     * ex: `state` tinyint unsigned => `status` tinyint unsigned
                     */
                    if (in_array($createdColumnType, $deletedColumns)) {
                        $deletedColumnName = array_search($createdColumnType, $deletedColumns);
                        unset($deletedColumns[$deletedColumnName]);
                        // renamed
                        $upSql[]   = "ALTER TABLE `{$tableName}` RENAME COLUMN `{$deletedColumnName}` TO `{$createdColumnName}`;";
                        $downSql[] = "ALTER TABLE `{$tableName}` RENAME COLUMN `{$createdColumnName}` TO `{$deletedColumnName}`;";
                    } else {
                        // created
                        $after     = $newTableFields[array_search($createdColumnName, $newTableFields) - 1];
                        $upSql[]   = "ALTER TABLE `{$tableName}` ADD COLUMN `{$createdColumnName}` {$createdColumnType} AFTER `{$after}`;";
                        $downSql[] = "ALTER TABLE `{$tableName}` DROP COLUMN `{$createdColumnName}`;";
                    }
                }
            }

            if ($deletedColumns) {
                foreach ($deletedColumns as $deletedColumnName => $deletedColumnType) {
                    $after     = $oldTableFields[array_search($deletedColumnName, $oldTableFields) - 1];
                    $upSql[]   = "ALTER TABLE `{$tableName}` DROP COLUMN `{$deletedColumnName}`;";
                    $downSql[] = "ALTER TABLE `{$tableName}` ADD COLUMN `{$deletedColumnName}` {$deletedColumnType} AFTER `{$after}`;";
                }
            }
        }

        return [
            'up'   => implode("\n", $upSql),
            'down' => implode("\n", $downSql),
        ];
    }

    /**
     * @param array $newSchema
     * @param array $oldSchema
     *
     * @return array
     */
    public function checkIndexes(array $newSchema, array $oldSchema): array
    {
        $upSql   = [];
        $downSql = [];

        $oldSchema = self::indexedSchema($oldSchema);
        $newSchema = self::indexedSchema($newSchema);

        foreach ($newSchema as $tableName => $newTableSchema) {
            if (!isset($oldSchema[$tableName])) {
                continue;
            }
            $oldTableSchema = $oldSchema[$tableName];

            $createdIndexes = array_diff($newTableSchema['keys'], $oldTableSchema['keys']);
            foreach ($createdIndexes as $createdIndex) {
                $indexSql = trim(
                    str_replace(
                        [
                            'PRIMARY KEY',
                            'UNIQUE KEY',
                            'FULLTEXT KEY',
                            'SPACIAL',
                            'KEY',
                        ],
                        [
                            'PRIMARY',
                            'UNIQUE',
                            'FULLTEXT',
                            'SPACIAL',
                            'KEY',
                        ],
                        $createdIndex
                    )
                );
                preg_match('/[^`]+`([^`]+)`/', $createdIndex, $matches);

                $upSql[]   = "ALTER TABLE `{$tableName}` ADD {$indexSql};";
                $downSql[] = "ALTER TABLE `{$tableName}` DROP INDEX `{$matches[1]}`;";
            }

            $deletedIndexes = array_diff($oldTableSchema['keys'], $newTableSchema['keys']);
            foreach ($deletedIndexes as $deletedIndex) {
                $indexSql = trim(
                    str_replace(
                        [
                            'PRIMARY KEY',
                            'UNIQUE KEY',
                            'FULLTEXT KEY',
                            'SPACIAL',
                            'KEY',
                        ],
                        [
                            'PRIMARY',
                            'UNIQUE',
                            'FULLTEXT',
                            'SPACIAL',
                            'KEY',
                        ],
                        $deletedIndex
                    )
                );
                preg_match('/[^`]+`([^`]+)`/', $deletedIndex, $matches);

                $upSql[]   = "ALTER TABLE `{$tableName}` DROP INDEX `{$matches[1]}`;";
                $downSql[] = "ALTER TABLE `{$tableName}` ADD {$indexSql};";
            }
        }

        return [
            'up'   => implode("\n", $upSql),
            'down' => implode("\n", $downSql),
        ];
    }

    /**
     * @param array $newSchema
     * @param array $oldSchema
     *
     * @return array
     */
    public function checkConstraints(array $newSchema, array $oldSchema): array
    {
        $upSql   = [];
        $downSql = [];

        $oldSchema = self::indexedSchema($oldSchema);
        $newSchema = self::indexedSchema($newSchema);

        foreach ($newSchema as $tableName => $newTableSchema) {
            if (!isset($oldSchema[$tableName])) {
                continue;
            }
            $oldTableSchema = $oldSchema[$tableName];

            $createdConstraints = array_diff(
                $newTableSchema['constraints'],
                $oldTableSchema['constraints']
            );

            foreach ($createdConstraints as $createdConstraint) {
                // $createdConstraint = 'CONSTRAINT `a_test_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)';
                preg_match('/^[^`]+`([^`]+)`/', $createdConstraint, $matches);

                $upSql[]   = "ALTER TABLE `{$tableName}` ADD {$createdConstraint};";
                $downSql[] = "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$matches[1]}`;";
            }

            $deletedConstraints = array_diff(
                $oldTableSchema['constraints'],
                $newTableSchema['constraints']
            );

            foreach ($deletedConstraints as $deletedConstraint) {
                // $deletedConstraint = 'CONSTRAINT `a_test_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)';
                preg_match('/^[^`]+`([^`]+)`/', $deletedConstraint, $matches);

                $upSql[]   = "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$matches[1]}`;";
                $downSql[] = "ALTER TABLE `{$tableName}` ADD {$deletedConstraint};";
            }
        }

        return [
            'up'   => implode("\n", $upSql),
            'down' => implode("\n", $downSql),
        ];
    }

    /**
     * @param array $schema
     *
     * @return array
     */
    public static function indexedSchema(array $schema)
    {
        $result = [];

        foreach ($schema as $tableData) {
            $result[$tableData['table']] = $tableData;
        }

        return $result;
    }

    /**
     * @param string $table
     *
     * @return mixed
     */
    public function tableCreateSql(string $table)
    {
        return $this->pdo
                   ->query("SHOW CREATE TABLE `{$table}`", \PDO::FETCH_NUM)
                   ->fetchAll()[0][1];
    }

    /**
     * @param string $sql
     *
     * @return array
     */
    public static function tableSqlToSchema(string $sql): array
    {
        $schema = [
            'table'       => '',
            'fields'      => [],
            'keys'        => [],
            'constraints' => [],
        ];

        $lines      = explode("\n", $sql);
        $createLine = array_shift($lines);
        array_pop($lines);

        preg_match('/`([^`]+)`/', $createLine, $matches);

        $schema['table'] = $matches[1];

        foreach ($lines as $line) {
            if (preg_match('/^`([a-zA-Z0-9_]+)` ([^`]+)$/', trim($line, ' ,'), $matches)) {
                $columnName   = $matches[1];
                $columnSchema = $matches[2];

                $schema['fields'][$columnName] = $columnSchema;
            } elseif (substr(trim($line), 0, 10) == 'CONSTRAINT') {
                $schema['constraints'][] = trim($line, ' ,');
            } elseif (strstr($line, ' KEY ')) {
                $schema['keys'][] = trim($line, ' ,');
            }
        }

        return $schema;
    }

    /**
     * @param array $tableSchema
     *
     * @return string
     */
    public static function tableSchemaToSql(array $tableSchema)
    {
        $lines = [];

        foreach ($tableSchema['fields'] as $name => $type) {
            $lines[] = "  `{$name}` {$type},";
        }

        foreach ($tableSchema['keys'] as $line) {
            $lines[] = "  {$line},";
        }

        foreach ($tableSchema['constraints'] as $line) {
            $lines[] = "  {$line},";
        }

        return trim(
            sprintf(
                "%s (\n%s\n)%s",
                "CREATE TABLE `{$tableSchema['table']}`",
                rtrim(implode("\n", $lines), ' ,'),
                ";"
            )
        );
    }
}
