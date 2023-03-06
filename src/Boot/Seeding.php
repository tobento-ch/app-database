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
use Tobento\App\Boot\Dater;
use Tobento\Service\Dater\DateFormatter;
use Tobento\Service\Seeder\SeedInterface;
use Tobento\Service\Seeder\Seed;
use Tobento\Service\Seeder\Resources;
use Tobento\Service\Seeder\Resource;
use Tobento\Service\Seeder\ResourceSeeder;
use Tobento\Service\Seeder\DateTimeSeeder;
use Tobento\Service\Seeder\UserSeeder;

/**
 * Seeding
 */
class Seeding extends Boot
{
    public const INFO = [
        'boot' => 'Implements SeedInterface from service/seeder',
    ];

    public const BOOT = [
        Dater::class,
    ];

    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->set(SeedInterface::class, function(): SeedInterface {
            $seed = new Seed(new Resources());
            
            $seed->addSeeder('resource', new ResourceSeeder($seed));
            
            $seed->addSeeder(
                'dateTime',
                new DateTimeSeeder($this->app->get(DateFormatter::class))
            );
            
            $seed->addSeeder('user', new UserSeeder($seed));
            
            return $seed;
        });
    }
}