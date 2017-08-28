<?php
namespace Tests\API\Service;

use Tests\TestCase;

use API\Config;
use API\Bootstrap;
use API\Container;
use API\Service\Auth;

use MongoDB\BSON\ObjectID as MongoObjectID;

use API\HttpException;

class AuthTest extends TestCase
{
    private $mockScopes = null;
    private $container = null;

    public function setUp()
    {
        if(!$this->mockScopes) {
            $this->mockScopes = json_decode(file_get_contents(dirname(__FILE__).'/mock_config_supported_auth_scopes.json'), true);
        }

        Bootstrap::reset();
        Bootstrap::factory(Bootstrap::Testing);
        $this->container = Bootstrap::getContainer();
    }

    ////
    // without mock data
    ////

    public function testConstructor()
    {
        $aut = new Auth($this->container);
        $this->assertTrue(is_array($aut->getAuthScopes()));
    }

    public function testConstructorConfigAuthScopesLoaded()
    {
        $aut = new Auth($this->container);
        $scopes = $aut->getAuthScopes();

        $this->assertTrue(is_array($scopes) && !empty($scopes));
    }

    public function testMockAuthScopes()
    {
        $aut = new Auth($this->container);
        $aut->mockAuthScopes($this->mockScopes);
    }

    public function testMockAuthScopesThrowsRunTimeExceptionIfBootstrapMode()
    {
        Bootstrap::reset();
        Bootstrap::factory(Bootstrap::Config);
        $container = new Container();

        $this->expectException(\RuntimeException::class);
        $aut = new Auth($container);
        $aut->mockAuthScopes($this->mockScopes);
    }

    ////
    // mock data
    ////

    /**
     * @depends testMockAuthScopes
     */
    public function testGetAuthScopes()
    {
        $aut = new Auth($this->container);
        $aut->mockAuthScopes($this->mockScopes);

        $scopes = $aut->getAuthScopes();
        $this->assertEquals(array_keys($scopes), array_keys($this->mockScopes), 'returns all authScopes');
    }

    public function testGetUserIdBeforeRegister()
    {
        $aut = new Auth($this->container);
        $uid = $aut->getUserId();
        $this->assertNull($uid);
    }

    public function testRegister()
    {
        $aut = new Auth($this->container);
        $aut->mockAuthScopes($this->mockScopes);

        $aut->register('testuserid', [ 'super' ]);

        $uid = $aut->getUserId();
        $this->assertEquals($uid, 'testuserid');

        $perms = $aut->getPermissions();
        $this->assertEquals($perms, [ 'super' ]);
    }

    // register: userId can be Mongo ObjectId instance
    public function testRegisterUsertIdIsMongoObjectID()
    {
        $aut = new Auth($this->container);
        $aut->mockAuthScopes($this->mockScopes);

        $mid = new MongoObjectID();
        $aut->register($mid, []);

        $uid = $aut->getUserId();
        $this->assertEquals($uid, $mid->__toString());
    }

    // register: permissions who are not in config are stripped
    public function testRegisterStripInvalidPermissions()
    {
        $aut = new Auth($this->container);
        $aut->mockAuthScopes($this->mockScopes);

        $aut->register('testuserid', [ 'super', 'invalid1', 'define', 'invalid2', false, 0, 123, '' ]);
        $perms = $aut->getPermissions();

        $this->assertTrue(in_array('super', $perms));
        $this->assertTrue(in_array('define', $perms));
        $this->assertFalse(in_array('invalid1', $perms));
        $this->assertFalse(in_array('invalid2', $perms));
        $this->assertFalse(in_array(false, $perms));
        $this->assertFalse(in_array(123, $perms));
        $this->assertFalse(in_array(0, $perms, TRUE));
        $this->assertFalse(in_array('', $perms));
    }

    /**
     * @depends testRegister
     */
    public function mergeInheritance()
    {
        $mock = [
            'parent' => [
                'inherits' => ['child', 'nonExisting']
            ],
            'notUniqueChilds' => [
                'inherits' => ['child', 'child']
            ],
            'child' => [
                'inherits' => ['subChild']
            ],
            'subchild' => [
                'inherits' => ['parent']
            ],
            'noChild' => [
                'inherits' => []
            ],
            'noInheritsProp' => [],
        ];
        $aut = new Auth($this->container);
        $aut->mockAuthScopes($mock);

        //
        $perms = $aut->mergeInheritance(['parent', 'notInConfiguration']);
        $this->assertFalse(in_array('parent', $perms), 'Doesn\'t merge in submitted names');

        // only merge first level childs
        $this->assertFalse(in_array('subChild', $perms), 'One level inheritance only: doesn\'t include childs of childs (only first children)');

        // strip out invalid permissions in both arguments and child configuration
        $this->assertFalse(in_array('notInfConfiguration', $perms), 'Strip unkown: doesn\'t include childs of childs');
        $this->assertFalse(in_array('nonExisting', $perms),         'Misconfiguration: doesn\'t include childs which are not top-level properties');

        // strict check
        $this->assertEquals($perms, ['child']);

        // uniqueness
        $perms = $aut->mergeInheritance(['parent', 'child', 'parent']);
        $unique = array_unique($perms);
        $this->assertEquals($perms, $unique, 'Returns unique set of permissions');
        $this->assertEquals($perms, ['child', 'subChild'], 'Merges first children of submitted permissions');

        // ensure uniqueness of childs even if misconfigured
        $perms = $aut->mergeInheritance(['notUniqueChilds']);
        $unique = array_unique($perms);
        $this->assertEquals($perms, $unique, 'Misconfiguration: returns unique set of permissions');

        // (currently) no hierarchical logic supported
        $perms  = $aut->mergeInheritance(['subChild']);
        $this->assertTrue(in_array('parent', $perms), 'No hierarchy: inheritance is not hierarchy');

        // invalid args
        $perms  = $aut->mergeInheritance(['noChild', 0, 123, null, '', true, false]);
        $this->assertEquals($perms, ['noChild'], 'Strip: invalid elements in argument array');

        // mssing "inherits" property in config
        $perms  = $aut->mergeInheritance(['noInheritsProp']);
        $this->assertEquals($perms, ['noInheritsProp'], 'Misconfiguration: no "inherits" property');
    }

    /**
     * @depends testRegister
     */
    public function mergeInheritanceFor()
    {
        $mock = [
            'parent' => [
                'inherits' => ['child', 'nonExisting']
            ],
            'notUniqueChilds' => [
                'inherits' => ['child', 'child']
            ],
            'child' => [
                'inherits' => ['subChild']
            ],
            'subchild' => [
                'inherits' => ['parent']
            ],
            'noChild' => [
                'inherits' => []
            ],
            'noInheritsProp' => [],
        ];
        $aut = new Auth($this->container);
        $aut->mockAuthScopes($mock);

        //
        $perms = $aut->mergeInheritanceFor('parent');
        $this->assertFalse(in_array('parent', $perms), 'Doesn\'t merge in submitted name');

        // only merge first level childs
        $this->assertFalse(in_array('subChild', $perms), 'One level inheritance only: doesn\'t include childs of childs (only first children)');

        // strip out invalid permission argument
        $perms = $aut->mergeInheritanceFor('notInConfiguration');
        $this->assertEquals($perms, [], 'Handle unkown: returns empty array');

        // strict check
        $this->assertEquals($perms, ['child']);

        // uniqueness
        $perms = $aut->mergeInheritanceFor('parent');
        $unique = array_unique($perms);
        $this->assertEquals($perms, $unique,  'Returns unique set of permissions');

        // ensure uniqueness of childs even if misconfigured
        $perms = $aut->mergeInheritanceFor('notUniqueChilds');
        $unique = array_unique($perms);
        $this->assertEquals($perms, $unique, 'Misconfiguration: returns unique set of permissions');

        // (currently) no hierarchical logic supported
        $perms  = $aut->mergeInheritanceFor('subChild');
        $this->assertTrue(in_array('parent', $perms), 'No hierarchy: inheritance is not hierarchy');
    }

    /**
     * @depends testRegister
     */
    public function testHasPermission()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @depends testRegister
     */
    public function testRequirePermission()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @depends testRegister
     */
    public function testGetUserId()
    {
        $this->assertTrue(true, 'It was tested before');
    }

    /**
     * @depends testRegister
     */
    public function testPermissions()
    {
        $this->assertTrue(true, 'It was tested before');
    }
}
