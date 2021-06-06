<?php

$env = file_exists(__DIR__ . '/.env')
    ? parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW)
    : getenv();

$adapter = $env['DB_ADAPTER'];
$name    = $env['DB_NAME'];
$charset = $env['DB_CHAR'];
$host    = $env['DB_REMOTE'] ? $env['DB_REMOTE_HOST'] : $env['DB_HOST'];
$port    = $env['DB_REMOTE'] ? $env['DB_REMOTE_PORT'] : $env['DB_PORT'];
$user    = $env['DB_REMOTE'] ? $env['DB_REMOTE_USER'] : $env['DB_USER'];
$pass    = $env['DB_REMOTE'] ? $env['DB_REMOTE_PASS'] : $env['DB_PASS'];

if ($env['PROPEL_PROFILE'] ?? false) {
    $classname = 'Propel\Runtime\Connection\ProfilerConnectionWrapper';
} elseif ($env['PROPEL_DEBUG'] ?? false) {
    $classname = 'Propel\Runtime\Connection\DebugPDO';
} else {
    $classname = 'Propel\Runtime\Connection\ConnectionWrapper';
}

return [
    'propel' => [
        'exclude_tables' => [
            'spartan_migration',
        ],
        'database'       => [
            'connections' => [
                $name => [
                    'adapter'     => $adapter,
                    'dsn'         => "{$adapter}:host={$host};port={$port};dbname={$name}",
                    'user'        => $user,
                    'password'    => $pass,
                    'settings'    => [
                        'charset' => $charset,
                        'queries' => [],
                    ],
                    'classname'   => $classname,
                    'model_paths' => ['src', 'vendor'],
                    'slaves'      => [],
                ],
            ],
        ],
        'runtime'        => [
            'defaultConnection' => $name,
            'connections'       => [$name],
            'profiler'          => [
                'classname'    => '\\Propel\\Runtime\\Connection\\ProfilerConnectionWrapper',
                'slowTreshold' => 0,
                'details'      => [
                    'time'     => [
                        'name'      => 'Time',
                        'precision' => 3,
                        'pad'       => 8,
                    ],
                    'mem'      => [
                        'name'      => 'Memory',
                        'precision' => 3,
                        'pad'       => 8,
                    ],
                    'memDelta' => [
                        'name'      => 'Memory Delta',
                        'precision' => 3,
                        'pad'       => 8,
                    ],
                    'memPeak'  => [
                        'name'      => 'Memory Peak',
                        'precision' => 3,
                        'pad'       => 8,
                    ],
                ],
                'outerGlue'    => " || ",
                'innerGlue'    => ': ',
            ],
        ],
    ],
];
