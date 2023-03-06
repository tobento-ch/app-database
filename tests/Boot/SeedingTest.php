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
use Tobento\App\Database\Boot\Seeding;
use Tobento\Service\Seeder\SeedInterface;
use Tobento\Service\Filesystem\Dir;

/**
 * SeedingTest
 */
class SeedingTest extends TestCase
{
    public function testInterfaces()
    {
        $app = (new AppFactory())->createApp();
        $app->boot(Seeding::class);
        $app->booting();
        
        $this->assertInstanceof(
            SeedInterface::class,
            $app->get(SeedInterface::class)
        );
    }
    
    public function testDefaultSeedersAreAvailable()
    {
        $app = (new AppFactory())->createApp();
        $app->boot(Seeding::class);
        $app->booting();
        $seed = $app->get(SeedInterface::class);
        
        // resource seeder:
        $this->assertIsString($seed->itemFrom(resource: 'countries'));
        
        // datetime seeder
        $this->assertIsString($seed->month(from: 2, to: 10));
        
        // user seeder
        $this->assertIsString($seed->email());
    }
}