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

namespace Tobento\App\Database\Test\Boot;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppFactory;
use Tobento\App\AppInterface;
use Tobento\App\Database\Boot\Database;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\PdoDatabaseInterface;
use Tobento\Service\Database\Storage\StorageDatabaseInterface;
use Tobento\Service\Database\Processor\ProcessorInterface;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Filesystem\Dir;

/**
 * DatabaseTest
 */
class DatabaseTest extends TestCase
{
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
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterfaces()
    {
        $app = $this->createApp();
        $app->boot(Database::class);
        $app->booting();
        
        $this->assertInstanceof(
            DatabasesInterface::class,
            $app->get(DatabasesInterface::class)
        );
        
        $this->assertInstanceof(
            PdoDatabaseInterface::class,
            $app->get(PdoDatabaseInterface::class)
        );

        $this->assertInstanceof(
            StorageInterface::class,
            $app->get(StorageInterface::class)
        );
        
        $this->assertInstanceof(
            ProcessorInterface::class,
            $app->get(ProcessorInterface::class)
        );
    }
    
    public function testDefaultDatabases()
    {
        $app = $this->createApp();
        $app->boot(Database::class);
        $app->booting();
        
        $this->assertInstanceof(
            PdoDatabaseInterface::class,
            $app->get(DatabasesInterface::class)->default('pdo')
        );
        
        $this->assertInstanceof(
            StorageDatabaseInterface::class,
            $app->get(DatabasesInterface::class)->default('storage')
        );
    }
}