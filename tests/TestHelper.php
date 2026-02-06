<?php

declare(strict_types=1);

namespace Tests;

use PDO;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Test helper for setting up in-memory databases with Phinx migrations
 */
class TestHelper
{
    /**
     * Run Phinx migrations on an in-memory PDO connection
     *
     * @param PDO $pdo In-memory PDO connection
     * @return void
     */
    public static function runPhinxMigrations(PDO $pdo): void
    {
        // Load Phinx configuration
        $configArray = require __DIR__ . '/../phinx.php';
        
        // Override testing environment to use our PDO connection
        $configArray['environments']['testing']['connection'] = $pdo;
        
        // Create Phinx config object
        $config = new Config($configArray);
        
        // Create migration manager
        $input = new ArrayInput(['--environment' => 'testing']);
        $output = new NullOutput();
        $manager = new Manager($config, $input, $output);
        
        // Run all migrations
        $manager->migrate('testing');
    }
}
