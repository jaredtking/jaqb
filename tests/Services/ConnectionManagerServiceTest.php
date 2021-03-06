<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace JAQB\Tests\Services;

use Infuse\Application;
use JAQB\Services\ConnectionManager;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery;

class ConnectionManagerServiceTest extends MockeryTestCase
{
    public function testInvoke()
    {
        $config = [
            'database' => [
                'main' => [
                    'type' => 'mysql',
                    'host' => '10.0.0.1',
                    'name' => 'mydb',
                    'user' => 'root',
                    'password' => '',
                ],
                'backup' => [
                    'type' => 'mysql',
                    'host' => '10.0.0.2',
                    'name' => 'mydb',
                    'user' => 'root',
                    'password' => '',
                ],
            ],
        ];
        $app = new Application($config);
        $app['pdo'] = Mockery::mock(PDO::class);
        $service = new ConnectionManager();

        $db = $service($app);
        $this->assertInstanceOf('JAQB\ConnectionManager', $db);
    }
}
