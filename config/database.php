<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use function Tobento\App\{directory};

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Names
    |--------------------------------------------------------------------------
    |
    | Specify the default database names you wish to use for your application.
    |
    | The default "pdo" is used by the application for the default
    | PdoDatabaseInterface implementation.
    | Moreover, it is used for autowiring classes with PDO parameters and
    | may be used in other app boots.
    | If you do not need it at all, just ignore or remove it.
    |
    | The default "storage" is used by the application for the default
    | StorageInterface implementation and may be used in other app boots.
    | If you do not need it at all, just ignore or remove it.
    |
    */

    'defaults' => [
        'pdo' => 'mysql',
        'storage' => 'file',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Databases
    |--------------------------------------------------------------------------
    |
    | Configure any databases needed for your application.
    |
    */
    
    'databases' => [
        
        'mysql' => [
            'factory' => \Tobento\Service\Database\PdoDatabaseFactory::class,
            'config' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => null,
                'database' => 'app',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
        ],
        
        'file' => [
            'factory' => \Tobento\Service\Database\Storage\StorageDatabaseFactory::class,
            'config' => [
                'storage' => Tobento\Service\Storage\JsonFileStorage::class,
                'dir' => directory('app').'storage/database/file/',
            ],
        ],
        
        'mysql-storage' => [
            'factory' => \Tobento\Service\Database\Storage\StorageDatabaseFactory::class,
            'config' => [
                'storage' => \Tobento\Service\Storage\PdoMySqlStorage::class,
                'database' => 'mysql',
            ],
        ],
       
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Database Processors
    |--------------------------------------------------------------------------
    |
    | The processors used for migration.
    |
    */
    
    'processors' => [
        // Processor for MySql and MariaDb:
        \Tobento\Service\Database\Processor\PdoMySqlProcessor::class,
        
        \Tobento\Service\Database\Storage\StorageDatabaseProcessor::class,
    ],

];