<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Config;
use API\Bootstrap;
use API\Storage\Adapter\Mongo;

class MongoTest extends MongoTestCase
{
    public function testTestConnection()
    {
        $uri = Config::get(['storage', 'Mongo', 'host_uri']);
        $result = Mongo::testConnection($uri);
        $this->assertTrue(is_object($result));
        $this->assertTrue(!empty($result->version));
    }

    public function testSupportsCommand()
    {
        $mongo = new Mongo(Bootstrap::getContainer());

        $result = $mongo->supportsCommand('buildInfo');
        $this->assertTrue($result);
        $result = $mongo->supportsCommand('notAMongoCommand');
        $this->assertFalse($result);
    }

    public function testGetDatabaseVersion()
    {
        $mongo = new Mongo(Bootstrap::getContainer());

        $result = $mongo->getDatabaseVersion();
        $this->assertNotEmpty($result);
    }
}
