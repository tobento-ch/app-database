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

namespace Tobento\App\Database\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppFactory;
use Tobento\App\AppInterface;
use Tobento\App\Database\Boot\Database;
use Tobento\App\Database\Test\Migration\DbMigration;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\Databases;
use Tobento\Service\Database\PdoDatabase;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\PdoMySqlStorage;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Database\Storage\StorageDatabase;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Migration\MigrationInstallException;
use PDO;

/**
 * MigrationTest
 */
class MigrationTest extends TestCase
{
    protected null|DatabasesInterface $databases = null;
            
    protected function setUp(): void
    {
        if (! getenv('TEST_TOBENTO_DATABASE_PDO_MYSQL')) {
            $this->markTestSkipped('Migration tests are disabled');
        }

        $pdo = new PDO(
            dsn: getenv('TEST_TOBENTO_DATABASE_PDO_MYSQL_DSN'),
            username: getenv('TEST_TOBENTO_DATABASE_PDO_MYSQL_USERNAME'),
            password: getenv('TEST_TOBENTO_DATABASE_PDO_MYSQL_PASSWORD'),
        );
                
        $this->databases = new Databases(
            new PdoDatabase(pdo: $pdo, name: 'mysql'),
            new StorageDatabase(
                storage: new PdoMySqlStorage(
                    pdo: $pdo,
                    tables: (new Tables())->add('products', ['id', 'sku'], 'id'),
                ),
                name: 'mysql-storage',
            ),
            new StorageDatabase(
                storage: new JsonFileStorage(
                    dir: __DIR__.'/json-file/',
                    tables: (new Tables())->add('countries', ['id', 'code'], 'id'),
                ),
                name: 'file',
            ),
        );
        
        $this->databases->addDefault(name: 'pdo', database: 'mysql');
        $this->databases->addDefault(name: 'storage', database: 'mysql-storage');
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        (new Dir())->create(__DIR__.'/../app/');
        (new Dir())->create(__DIR__.'/../app/config/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config');
        
        return $app;
    }
    
    public function testDbMigration()
    {
        $app = $this->createApp();
        $app->boot(Database::class);
        $app->booting();
        
        $app->on(DatabasesInterface::class, function() {
            return $this->databases;
        });
        
        // Install migration:
        try {
            $app->install(DbMigration::class);   
        } catch (MigrationInstallException $e) {
            // ignore for testing;
        }

        // Default pdo:
        $database = $app->get(DatabasesInterface::class)->default('pdo');

        $userNames = $database->execute(
            statement: 'SELECT name FROM users',
        )->fetchAll(PDO::FETCH_COLUMN);
        
        $this->assertSame(['John', 'Mia'], $userNames);
        
        // Default storage:
        $database = $app->get(DatabasesInterface::class)->default('storage');
        
        $skus = $database->storage()->table('products')->column('sku')->all();
        
        $this->assertSame(['pen', 'pencil'], $skus);
        
        // File storage:
        $database = $app->get(DatabasesInterface::class)->get('file');
        
        $codes = $database->storage()->table('countries')->column('code')->all();
        
        $this->assertSame(['USA', 'CH'], $codes);
    }
}