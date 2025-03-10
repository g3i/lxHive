<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Bootstrap;
use API\Storage\Adapter\Mongo\Log;

class LogTest extends MongoTestCase
{
    private $collection;

    public function setUp(): void
    {
        $this->collection = Log::COLLECTION_NAME;
    }

    public function testGetIndexes()
    {
        $coll = new Log(Bootstrap::getContainer());
        $indexes = $coll->getIndexes();

        $this->assertTrue(is_array($indexes));
    }

    /**
     * @depends testGetIndexes
     */
    public function testInstall()
    {
        $this->dropCollection($this->collection);

        $coll = new Log(Bootstrap::getContainer());
        $coll->install();
        // has passed without exception

        $indexes = $this->command([
            'listIndexes' => $this->collection
        ])->toArray();

        $configured = array_map(function($i) {
            return $i['name'];
        }, $coll->getIndexes());

        $installed = array_map(function($i) {
            return $i->name;
        }, $indexes);

        foreach ($configured as $name) {
            $this->assertContains($name, $installed);
        }

        // #241 avoid 'risky' test flag, above tests are left for future reference
        $this->assertEquals(count($configured), 0, 'no indexes are defined for this collection');
    }
}
