<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Config;
use API\Storage\Adapter\Mongo;

class MongoTest extends MongoTestCase
{
    public function testTestConnection()
    {
        $uri = Config::get(['storage', 'Mongo', 'host_uri']);
        $result = Mongo::testConnection($uri);
        $this->assertTrue(is_string($result));
        $this->assertTrue(!empty($result));
    }
}
