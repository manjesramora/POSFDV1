<?php

use Illuminate\Support\Str;

return [

    'default' => env('DB_CONNECTION', 'sqlsrv'),  // Cambiado a 'sqlsrv' para que sea la conexión predeterminada

    'connections' => [
        'centro' => [
            'driver' => 'sqlsrv',  // Cambia esto si usas MySQL u otro sistema de BD
            'host' => env('DB_CENTRO_HOST', '128.76.8.148'), // Configura con tu host
            'port' => env('DB_CENTRO_PORT', '1433'),
            'database' => env('DB_CENTRO_DATABASE', 'ERP_TBI_TEC_PRO_FDGO'),
            'username' => env('DB_CENTRO_USERNAME', 'USRTEST'),
            'password' => env('DB_CENTRO_PASSWORD', 'PROTOTIPO'),
            'charset' => 'utf8',
            'prefix' => '',
        ],


        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
        // Conexión para centro de costo 'FD09'
        'FD09' => [
            'driver' => 'sqlsrv',
            'host' => '128.76.8.148',
            'port' => '1433',
            'database' => 'ERP_TBI_TEC_PRO_FDGO',
            'username' => 'USRTEST',
            'password' => 'PROTOTIPO',
            'charset' => 'utf8',
            'prefix' => '',
        ],

        // Conexión para centro de costo 'FD10'
        'FD10' => [
            'driver' => 'sqlsrv',
            'host' => '128.76.8.245',
            'port' => '1433',
            'database' => 'ERP_TBI_TEC_PRO_FDGO',
            'username' => 'USRTEST',
            'password' => 'PROTOTIPO',
            'charset' => 'utf8',
            'prefix' => '',
        ],


        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    'migrations' => 'migrations',

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
