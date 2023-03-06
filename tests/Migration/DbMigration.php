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

namespace Tobento\App\Database\Test\Migration;

use PHPUnit\Framework\TestCase;

use Tobento\Service\Database\Migration\DatabaseMigration;
use Tobento\Service\Database\Schema\Table;

class DbMigration extends DatabaseMigration
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
                $table->string('name');
                $table->items(iterable: [
                    ['name' => 'John'],
                    ['name' => 'Mia'],
                ]);
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
                $table->string('sku');
                $table->items(iterable: [
                    ['sku' => 'pen'],
                    ['sku' => 'pencil'],
                ]);
                return $table;
            },
            database: $this->databases->default('storage'),
            name: 'Products',
            description: 'Products desc',            
        );
        
        $this->registerTable(
            table: function(): Table {
                $table = new Table(name: 'countries');
                $table->primary('id');
                $table->string('name');
                $table->items(iterable: [
                    ['code' => 'USA'],
                    ['code' => 'CH'],
                ]);
                return $table;
            },
            database: $this->databases->get('file'),
            name: 'Countries',
            description: 'Countries desc',            
        );
    }
}