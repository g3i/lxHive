<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Bootstrap;
use API\Storage\Adapter\Mongo\OAuth;

class OAuthTest extends MongoTestCase
{
    private $collection;

    public function setUp(): void
    {
        $this->collection = OAuth::COLLECTION_NAME;
    }

    public function testGetIndexes()
    {
        $coll = new OAuth(Bootstrap::getContainer());
        $indexes = $coll->getIndexes();

        $this->assertTrue(is_array($indexes));
    }

    /**
     * @depends testGetIndexes
     */
    public function testInstall()
    {
        $this->dropCollection($this->collection);

        $coll = new OAuth(Bootstrap::getContainer());
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
        //storeToken($expiresAt, $user, $client, array $permissions = [], $code = null)
        $now = time();
        $mock = (object)[
            'expiresAt' => $now + 3600,
            'user' => (object) [
                '_id' => new \MongoDB\BSON\ObjectID()
            ],
            'client' => (object) [
                '_id' => new \MongoDB\BSON\ObjectID()
            ],
            'permissions' => ['statement/write', 'statments/read/mine'],
        ];

        $service = new OAuth(Bootstrap::getContainer());
        $res = $service->storeToken($mock->expiresAt, $mock->user, $mock->client, $mock->permissions);

        // fetch LAST record independently to rule out any side effects
        $q = $this->query(OAuth::COLLECTION_NAME, [], [
            'sort' => [
                '_id' => 1
            ]
        ]);
        $t = $q->toArray()[0];

        $this->assertEquals($t->expiresAt->toDateTime()->getTimestamp(), $mock->expiresAt);
        $this->assertEquals((string) $t->userId, (string) $mock->user->_id);
        $this->assertEquals($t->permissions, $mock->permissions);
    }
}
