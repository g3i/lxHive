<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Bootstrap;
use API\Storage\Adapter\Mongo\OAuthClients;

class OAuthClientsTest extends MongoTestCase
{
    private $collection;

    public function setUp()
    {
        $this->collection = OAuthClients::COLLECTION_NAME;
    }

    public function testGetIndexes()
    {
        $coll = new OAuthClients(Bootstrap::getContainer());
        $indexes = $coll->getIndexes();

        $this->assertTrue(is_array($indexes));
    }

    /**
     * @depends testGetIndexes
     */
    public function testInstall()
    {
        $this->dropCollection($this->collection);

        $coll = new OAuthClients(Bootstrap::getContainer());
        $coll->install();
        // has passed without exception

        $indexes = $this->command([
            'listIndexes' => $this->collection
        ])->toArray();
        $configured = array_keys($coll->getIndexes());
        $installed = array_map(function($i) {
            return $i->name;
        }, $indexes);

        foreach ($configured as $name) {
            $this->assertContains($name, $installed);
        }
    }
}
