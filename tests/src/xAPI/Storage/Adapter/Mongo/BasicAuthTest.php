<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Bootstrap;
use API\Storage\Adapter\Mongo\BasicAuth;

class BasicAuthTest extends MongoTestCase
{
    private $collection;

    public function setUp(): void
    {
        $this->collection = BasicAuth::COLLECTION_NAME;
    }

    public function testGetIndexes()
    {
        $coll = new BasicAuth(Bootstrap::getContainer());
        $indexes = $coll->getIndexes();

        $this->assertTrue(is_array($indexes));
    }

    /**
     * @depends testGetIndexes
     */
    public function testInstall()
    {
        $this->dropCollection($this->collection);

        $coll = new BasicAuth(Bootstrap::getContainer());
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
    }

    /**
     * @depends testInstall
     *
     * Note: we are NOT testing any user relations
     */
    public function testStoreToken()
    {
        $now = time();
        $mock = (object)[
            'name' => 'testStoreToken',
            'description' => 'testdescription',
            'expiresAt' => $now + 3600,
            'user' => (object) [
                '_id' => new \MongoDB\BSON\ObjectID()
            ],
            'client' => (object) [
                '_id' => new \MongoDB\BSON\ObjectID()
            ],
            'permissions' => ['statement/write', 'statments/read/mine'],
        ];

        $service = new BasicAuth(Bootstrap::getContainer());
        $service->storeToken($mock->name, $mock->description, $mock->expiresAt, $mock->user, $mock->permissions);

        // fetch record independently to rule out any side effects
        $q = $this->query(BasicAuth::COLLECTION_NAME, ['name' => $mock->name]);
        $t = $q->toArray()[0];

        $this->assertEquals($t->name, $mock->name);
        $this->assertEquals($t->description, $mock->description);
        $this->assertEquals($t->expiresAt->toDateTime()->getTimestamp(), $mock->expiresAt);
        $this->assertEquals((string) $t->userId, (string) $mock->user->_id);
        $this->assertEquals($t->permissions, $mock->permissions);

        $this->assertTrue(isset($t->key), 'a default key was created');
        $this->assertTrue(isset($t->secret), 'a default secret was created');
        $this->assertGreaterThan(3, strlen($t->key));
        $this->assertGreaterThan(3, strlen($t->secret));
    }
}
