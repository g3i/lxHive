<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Bootstrap;
use API\Storage\Adapter\Mongo\User;

use API\Storage\AdapterException;

class UserTest extends MongoTestCase
{
    private $collection;

    // storage cache for written user record
    private static $userObjectId = null;
    private static $userEmail = null;

    public function setUp(): void
    {
        $this->collection = User::COLLECTION_NAME;
    }

    public function testGetIndexes()
    {
        $coll = new User(Bootstrap::getContainer());
        $indexes = $coll->getIndexes();

        $this->assertTrue(is_array($indexes));
    }

    /**
     * @depends testGetIndexes
     */
    public function testInstall()
    {
        $this->dropCollection($this->collection);

        $coll = new User(Bootstrap::getContainer());
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
     */
    public function testAddUser()
    {
        $now = time();
        $mock = (object)[
            'name' => 'testAddUser',
            'description' => 'testdescription',
            'email' => $now .'@testAddUser.com',
            'password' => 'TesT.'.$now,
            'permissions' => ['statement/write', 'statments/read/mine']
        ];

        $service = new User(Bootstrap::getContainer());
        $service->addUser($mock->name, $mock->description, $mock->email, $mock->password, $mock->permissions);

        // fetch record independently to rule out any side effects
        $q = $this->query(User::COLLECTION_NAME, ['email' => $mock->email]);
        $u = $q->toArray()[0];

        $this->assertEquals($u->name, $mock->name);
        $this->assertEquals($u->description, $mock->description);
        $this->assertEquals($u->email, $mock->email);
        $this->assertEquals($u->passwordHash, sha1($mock->password));
        $this->assertEquals($u->permissions, $mock->permissions);

        // cache record id and email for other tests
        self::$userObjectId = $u->_id;
        self::$userEmail = $u->email;
    }

    /**
     * @depends testAddUser
     */
    public function testAddUserUniqueEmail()
    {
        $now = time();
        $mock = (object)[
            'name' => 'testAddUserUniqueEmail',
            'description' => 'testdescription',
            'email' => $now .'@testAddUserUniqueEmail.com',
            'password' => 'TesT.'.$now,
            'permissions' => ['statement/write', 'statments/read/mine']
        ];

        $service = new User(Bootstrap::getContainer());
        $service->addUser($mock->name, $mock->description, $mock->email, $mock->password, $mock->permissions);

        $this->expectException(AdapterException::class);
        $service->addUser($mock->name, $mock->description, $mock->email, $mock->password, $mock->permissions);
    }

    /**
     * @depends testAddUser
     *
     * see self::$userObjectId;
     */
    public function testFindById()
    {
        $this->assertNotNull(self::$userObjectId);

        $service = new User(Bootstrap::getContainer());
        $q = $service->findById(self::$userObjectId);

        $this->assertEquals((string) $q->_id, (string) self::$userObjectId);
        $this->assertEquals($q->email, self::$userEmail);
    }

    /**
     * @depends testAddUser
     *
     * see self::$userObjectId;
     */
    public function testFindByIdNonexistingReturnsNull()
    {
        $service = new User(Bootstrap::getContainer());
        $oid = new \MongoDB\BSON\ObjectID();
        $q = $service->findById($oid);

        $this->assertEquals($q, null);
    }

}
