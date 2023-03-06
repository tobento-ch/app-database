<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Database\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Boot\Functions;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\Databases;
use Tobento\Service\Database\DatabaseInterface;
use Tobento\Service\Database\PdoDatabaseInterface;
use Tobento\Service\Database\DatabaseFactoryInterface;
use Tobento\Service\Database\Processor\ProcessorInterface;
use Tobento\Service\Database\Processor\Processors;
use Tobento\Service\Database\Storage\StorageDatabaseInterface;
use Tobento\Service\Database\DatabaseException;
use Tobento\Service\Storage\StorageInterface;
use PDO;

/**
 * Database
 */
class Database extends Boot
{
    public const INFO = [
        'boot' => 'Registers databases based on its configuration file',
    ];

    public const BOOT = [
        Functions::class,
        Config::class,
        Migration::class,
    ];

    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param Config $config
     * @return void
     */
    public function boot(Migration $migration, Config $config): void
    {
        // Install migrations:
        $migration->install(\Tobento\App\Database\Migration\Database::class);
        
        // Set databases implementation:
        $this->app->set(DatabasesInterface::class, function() use ($config): DatabasesInterface {
            
            // Load the database configuration without storing it:
            $config = $config->load(file: 'database.php');
            
            // Create and register the databases:
            $databases = new Databases();
            
            foreach($config['defaults'] ?? [] as $name => $database) {
                $databases->addDefault($name, $database);
            }
            
            foreach($config['databases'] ?? [] as $name => $params)
            {                                     
                $databases->register($name, function() use ($name, $params) {

                    $factory = $this->app->get($params['factory']);

                    if (! $factory instanceof DatabaseFactoryInterface)
                    {
                        throw new DatabaseException(
                            $name,
                            sprintf(
                                'Database config factory needs to be an instance of %s!',
                                DatabaseFactoryInterface::class
                            )
                        );
                    }

                    return $factory->createDatabase($name, $params['config']);
                });
            }
            
            return $databases;
        });
        
        // Default PdoDatabase:
        $this->app->set(PdoDatabaseInterface::class, function(): PdoDatabaseInterface {
            $database = $this->app->get(DatabasesInterface::class)->default('pdo');
            
            if (! $database instanceof PdoDatabaseInterface) {
                throw new DatabaseException(
                    $database->name(),
                    sprintf('Database for PDO needs to be an instance of %s!', PdoDatabaseInterface::class)
                );
            }
            
            return $database;
        });
        
        // Default PDO:
        $this->app->set(PDO::class, function(): PDO {
            return $this->app->get(PdoDatabaseInterface::class)->pdo();
        });
        
        // Default StorageInterface:
        $this->app->set(StorageInterface::class, function(): StorageInterface {
            $database = $this->app->get(DatabasesInterface::class)->default('storage');
            
            if (! $database instanceof StorageDatabaseInterface) {
                throw new DatabaseException(
                    $database->name(),
                    sprintf(
                        'Database for "storage" needs to be an instance of %s!',
                        StorageDatabaseInterface::class
                    )
                );
            }
            
            return $database->storage();
        });
        
        // Database Processor implementation:
        $this->app->set(ProcessorInterface::class, function() use ($config): ProcessorInterface {
            
            // Load the database configuration without storing it:
            $config = $config->load(file: 'database.php');
            
            $processors = [];
            
            foreach($config['processors'] ?? [] as $processor) {
                $processors[] = $this->app->get($processor);
            }
            
            return new Processors(...$processors);
        });
    }
}