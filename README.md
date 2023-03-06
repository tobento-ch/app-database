# App Database

Database support for the app.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Database Boot](#database-boot)
        - [Database Config](#database-config)
        - [Database Usage](#database-usage)
    - [Migration](#migration)
        - [Create Migration](#create-migration)
        - [Install And Uninstall Migration](#install-and-uninstall-migration)
        - [App Migration Example](#app-migration-example)
    - [Seeding](#seeding)
        - [Seeding Boot](#seeding-boot)
        - [Create Migration Seeder](#create-migration-seeder)
        - [Seeder Resources](#seeder-resources)
        - [App Migration Seeder Example](#app-migration-seeder-example)
    - [Repository](#repository)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app database project running this command.

```
composer require tobento/app-database
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Database Boot

The database boot does the following:

* Registers databases based on its configuration file
* Binds DatabasesInterface to the app container.
* Binds PdoDatabaseInterface to the app container using the default pdo database
* Binds PDO to the app container for autowiring by using the default pdo database
* Binds StorageInterface to the app container.

### Database Config

The configuration for the database is located in the ```app/config/database.php``` file at the default [App Skeleton](https://github.com/tobento-ch/app-skeleton#config) config location.

```php
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
```

**Pdo Database Factory**

Check out the [**Database Service - Pdo Database Factory**](https://github.com/tobento-ch/service-database#pdo-database-factory) for its config parameters.

**Storage Database Factory**

Check out the [**Database Storage Service**](https://github.com/tobento-ch/service-database-storage#storage-database-factory) for its documentation.

Check out the [**Storage Service - Storages**](https://github.com/tobento-ch/service-storage#storages) for the available storages.

### Database Usage

You may access the default databases by the app:

```php
use Tobento\App\AppFactory;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\PdoDatabaseInterface;
use Tobento\Service\Database\Processor\ProcessorInterface;
use Tobento\Service\Storage\StorageInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config');

// Adding boots
$app->boot(\Tobento\App\Database\Boot\Database::class);
$app->booting();

// Databases:
$databases = $app->get(DatabasesInterface::class);

// Default Pdo Database:
$database = $app->get(PdoDatabaseInterface::class);

// Default Storage:
$storage = $app->get(StorageInterface::class);

// Processor for migration:
$processor = $app->get(ProcessorInterface::class);

// Run the app
$app->run();
```

Check out the [**Database Service - Databases**](https://github.com/tobento-ch/service-database#databases) to learn more about the usage of the ```DatabasesInterface::class```.

Check out the [**Database Service - Using PDO Database**](https://github.com/tobento-ch/service-database#using-pdo-database) to learn more about the usage of the ```PdoDatabaseInterface::class```.

Check out the [**Storage Service - Queries**](https://github.com/tobento-ch/service-storage#queries) to learn more about the usage of the ```StorageInterface::class```.

**Using autowiring**

You can also request the databases or the processor in any class resolved by the app.

```php
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\PdoDatabaseInterface;
use Tobento\Service\Database\Processor\ProcessorInterface;
use Tobento\Service\Storage\StorageInterface;
use PDO;

class SomeService
{
    public function __construct(
        protected DatabasesInterface $databases,
        protected PdoDatabaseInterface $database,
        protected ProcessorInterface $processor,
        protected StorageInterface $storage,
        protected PDO $pdo,
    ) {}
}
```

## Migration

### Create Migration

Create a migration class by extending the ```DatabaseMigration::class``` and using the ```registerTables``` method to specifiy your tables.

```php
use Tobento\Service\Database\Migration\DatabaseMigration;
use Tobento\Service\Database\Schema\Table;

class DbMigrations extends DatabaseMigration
{
    public function description(): string
    {
        return 'db migrations';
    }

    /**
     * Register tables used by the install and uninstall methods
     * to create the actions from.
     *
     * @return void
     */
    protected function registerTables(): void
    {
        $this->registerTable(
            table: function(): Table {
                $table = new Table(name: 'users');
                $table->primary('id');
                //...
                return $table;
            },
            database: $this->databases->default('pdo'),
            name: 'Users',
            description: 'Users desc',
        );
        
        $this->registerTable(
            table: function(): Table {
                $table = new Table(name: 'products');
                $table->primary('id');
                //...
                return $table;
            },
            database: $this->databases->default('pdo'),
        );
    }
}
```

Check out the [**Database Service - Table Schema**](https://github.com/tobento-ch/service-database#table-schema) for its documentation.

Check out the [**Database Service - Create Migration**](https://github.com/tobento-ch/service-database#create-migration) to learn more about it.

### Install And Uninstall Migration

Check out the [**App Migration - Install And Uninstall Migration**](https://github.com/tobento-ch/app-migration#install-and-uninstall-migration) to learn more about it.

### App Migration Example

```php
use Tobento\App\AppFactory;
use Tobento\Service\Database\Migration\DatabaseMigration;
use Tobento\Service\Database\Schema\Table;
use Tobento\Service\Database\DatabasesInterface;

class DbMigrations extends DatabaseMigration
{
    public function description(): string
    {
        return 'db migrations';
    }

    protected function registerTables(): void
    {
        $this->registerTable(
            table: function(): Table {
                $table = new Table(name: 'users');
                $table->primary('id');
                $table->string('name');
                $table->items(iterable: [
                    ['name' => 'John'],
                    ['name' => 'Mia'],
                ]);
                return $table;
            },
            database: $this->databases->default('pdo'),
        );
    }
}

$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config');

// Adding boots:
$app->boot(\Tobento\App\Database\Boot\Database::class);
$app->booting();

// Install migration:
$app->install(DbMigrations::class);

// Fetch user names:
$database = $app->get(DatabasesInterface::class)->default('pdo');

$userNames = $database->execute(
    statement: 'SELECT name FROM users',
)->fetchAll(\PDO::FETCH_COLUMN);

// var_dump($userNames);
// array(2) { [0]=> string(4) "John" [1]=> string(3) "Mia" }

// Run the app:
$app->run();
```

## Seeding

### Seeding Boot

The seeding boot does the following:

* [*SeedInterface*](https://github.com/tobento-ch/service-seeder#create-seed) implementation

The following seeders will be availbale:

* [*Resource Seeder*](https://github.com/tobento-ch/service-seeder#resource-seeder)
* [*DateTime Seeder*](https://github.com/tobento-ch/service-seeder#datetime-seeder)
* [*User Seeder*](https://github.com/tobento-ch/service-seeder#user-seeder)

Keep in mind that no [*Resources*](https://github.com/tobento-ch/service-seeder#resources) are set as they may be specific to your app needs. Therefore, the seeders mostly using the [*Lorem Seeder*](https://github.com/tobento-ch/service-seeder#lorem-seeder) as fallback.

```php
use Tobento\App\AppFactory;
use Tobento\Service\Seeder\SeedInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Database\Boot\Seeding::class);
$app->booting();

$seed = $app->get(SeedInterface::class);

// Run the app
$app->run();
```

### Create Migration Seeder

Create a migration class for seeding by extending the ```DatabaseMigrationSeeder::class``` and using the ```registerTables``` method to specifiy your tables.

```php
use Tobento\Service\Database\Migration\DatabaseMigrationSeeder;
use Tobento\Service\Database\Schema\Table;
use Tobento\Service\Iterable\ItemFactoryIterator;

class DbMigrationsSeeder extends DatabaseMigrationSeeder
{
    public function description(): string
    {
        return 'db migrations seeding';
    }

    /**
     * Register tables used by the install and uninstall methods
     * to create the actions from.
     *
     * @return void
     */
    protected function registerTables(): void
    {
        $this->registerTable(
            table: function(): Table {
                $table = new Table(name: 'users');
                // no need to specifiy columns again
                // if you have migrated the table before.
                
                // seeding:
                $table->items(new ItemFactoryIterator(
                    factory: function(): array {
                    
                        $fullname = $this->seed->fullname();
                        
                        return [
                            'name' => $fullname,
                            'email' => $this->seed->email(from: $fullname),
                            'weekday' => $this->seed->locale(['de', 'en'])->weekday(1, 7, 'EEEE'),
                        ];
                    },
                    create: 10000
                ))
                ->chunk(length: 2000)
                ->useTransaction(false) // default is true
                ->forceInsert(true); // default is false
                
                return $table;
            },
            database: $this->databases->default('pdo'),
            name: 'Users seeding',
            description: 'Users seeded',
        );
    }
}
```

Check out the [**Database Service - Create Migration Seeder**](https://github.com/tobento-ch/service-database#create-migration-seeder) to learn more about it.

Check out the [**Seeder Service**](https://github.com/tobento-ch/service-seeder) for its documentation.

Check out the [**Database Service - Table Schema**](https://github.com/tobento-ch/service-database#table-schema) for its documentation.

### Seeder Resources

You may add seeder resources by the following ways:

**Globally by using the app on method**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Seeder\SeedInterface;
use Tobento\Service\Seeder\Resource;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Database\Boot\Seeding::class);
$app->booting();

// Add resources:
$app->on(SeedInterface::class, function(SeedInterface $seed) {

    $seed->resources()->add(new Resource('countries', 'en', [
        'Usa', 'Switzerland', 'Germany',
    ]));
});

$seed = $app->get(SeedInterface::class);

var_dump($seed->country());
// string(7) "Germany"

// Run the app
$app->run();
```

**Specific on your migration seeder**

```php
use Tobento\Service\Database\Migration\DatabaseMigrationSeeder;
use Tobento\Service\Seeder\Resource;

class DbMigrationsSeeder extends DatabaseMigrationSeeder
{
    // ...
    
    protected function registerTables(): void
    {
        $this->seed->resources()->add(new Resource('countries', 'en', [
            'Usa', 'Switzerland', 'Germany',
        ]));
        
        // register tables:
    }
}
```

You may check out [**Seeder Service - Resources**](https://github.com/tobento-ch/service-seeder#resources) or [**Seeder Service - Files Resources**](https://github.com/tobento-ch/service-seeder#files-resources) for its documentation.

### App Migration Seeder Example

```php
use Tobento\App\AppFactory;
use Tobento\Service\Database\Migration\DatabaseMigration;
use Tobento\Service\Database\Migration\DatabaseMigrationSeeder;
use Tobento\Service\Database\Schema\Table;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Iterable\ItemFactoryIterator;

class DbMigrations extends DatabaseMigration
{
    public function description(): string
    {
        return 'db migrations';
    }

    protected function registerTables(): void
    {
        $this->registerTable(
            table: function(): Table {
                $table = new Table(name: 'users');
                $table->primary('id');
                $table->string('name');
                $table->string('email');
                return $table;
            },
            database: $this->databases->default('pdo'),
        );
    }
}

class DbMigrationsSeeder extends DatabaseMigrationSeeder
{
    public function description(): string
    {
        return 'db migrations';
    }

    protected function registerTables(): void
    {
        $this->registerTable(
            table: function(): Table {
                $table = new Table(name: 'users');
                
                $table->items(new ItemFactoryIterator(
                    factory: function(): array {
                    
                        $fullname = $this->seed->fullname();
                        
                        return [
                            'name' => $fullname,
                            'email' => $this->seed->email(from: $fullname),
                        ];
                    },
                    create: 10,
                ))
                ->chunk(length: 2000)
                ->useTransaction(false) // default is true
                ->forceInsert(true); // default is false
                
                return $table;
            },
            database: $this->databases->default('pdo'),
        );
    }
}

$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config');

// Adding boots:
$app->boot(\Tobento\App\Database\Boot\Database::class);
$app->boot(\Tobento\App\Database\Boot\Seeding::class);
$app->booting();

// Install migration:
$app->install(DbMigrations::class);
$app->install(DbMigrationsSeeder::class);

// Fetch user names:
$database = $app->get(DatabasesInterface::class)->default('pdo');

$emailsToNames = $database->execute(
    statement: 'SELECT email, name FROM users',
)->fetchAll(\PDO::FETCH_KEY_PAIR);

var_dump($emailsToNames);
// array(10) { ["vitae.elit@example.org"]=> string(10) "Vitae Elit" ... }

// Run the app:
$app->run();
```

## Repository

In progress...

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)