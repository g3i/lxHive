<?php
namespace Tests\API;

use Tests\TestCase;

use API\Bootstrap;
use API\Admin\Setup;
use API\Admin\AdminException;

class SetupTest extends TestCase
{
    const ConfigDir = './src/xAPI/Config/';

    public static function setUpBeforeClass()
    {
        if(!is_writable(self::ConfigDir)) {
            throw new \RuntimeException(self::ConfigDir.' is not a writable directory.');
        }
    }

    // cleanup after tests
    public function tearDown()
    {
        if(file_exists(self::ConfigDir.'UnitTest.yml')) {
            unlink(self::ConfigDir.'UnitTest.yml');
        }
    }

    ////
    // Config yml manager
    ////

    public function testLocateYaml()
    {
        // NotFound
        $admin = new Setup();
        $file = $admin->locateYaml('UnitTest.yml');
        $this->assertFalse($file);

        // Found
        touch(self::ConfigDir.'UnitTest.yml');
        $file = $admin->locateYaml('UnitTest.yml');
        $this->assertStringEndsWith('/UnitTest.yml', $file);
    }

    /**
     * @depends testLocateYaml
     */
    public function testInstallYaml()
    {
        $admin = new Setup();
        $data = $admin->installYaml('UnitTest.yml');

        $this->assertFileExists(self::ConfigDir.'UnitTest.yml');
        $this->assertTrue(is_array($data));
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * @depends testLocateYaml
     */
    public function testInstallYamlInvalidTemplate()
    {
        $admin = new Setup();

        $this->expectException(AdminException::class);
        $data = $admin->installYaml('InvalidUnitTest.yml');
    }

    /**
     * @depends testLocateYaml
     */
    public function testInstallYamlMergeData()
    {
        $admin = new Setup();
        $now = time();

        $data = $admin->installYaml('UnitTest.yml', ['now' => $now ]);
        $this->assertArrayHasKey('now', $data);
        $this->assertEquals($data['now'], $now);
    }

    /**
     * @depends testInstallYaml
     */
    public function testLoadYaml()
    {
        $admin = new Setup();

        $admin->installYaml('UnitTest.yml');
        $data = $admin->loadYaml('UnitTest.yml');
        $this->assertTrue(is_array($data));
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * @depends testInstallYaml
     */
    public function testLoadYamlNotFound()
    {
        $admin = new Setup();

        $this->expectException(AdminException::class);
        $admin->loadYaml('InvalidUnitTest.yml');
    }

    /**
     * @depends testInstallYaml
     */
    public function testLoadYamlInvalidJson()
    {
        $admin = new Setup();

        file_put_contents(self::ConfigDir.'UnitTest.yml', '{invalid yml');
        $this->assertFileExists(self::ConfigDir.'UnitTest.yml');

        $this->expectException(AdminException::class);
        $data = $admin->loadYaml('UnitTest.yml');
    }

    /**
     * @depends testInstallYaml
     */
    public function testLoadYamlEmptyData()
    {
        $admin = new Setup();

        file_put_contents(self::ConfigDir.'UnitTest.yml', '');
        $this->assertFileExists(self::ConfigDir.'UnitTest.yml');

        $this->expectException(AdminException::class);
        $data = $admin->loadYaml('UnitTest.yml');
    }

    /**
     * @depends testInstallYaml
     */
    public function testUpdateYaml()
    {
        $admin = new Setup();
        $now = time();

        $admin->installYaml('UnitTest.yml', ['now' => 'a string' ]);
        $data = $admin->updateYaml('UnitTest.yml', ['now' => $now ]);

        $this->assertArrayHasKey('now', $data);
        $this->assertEquals($data['now'], $now);
    }

    /**
     * @depends testInstallYaml
     */
    public function testUpdateYamlNotFound()
    {
        $admin = new Setup();
        $now = time();

        $this->expectException(AdminException::class);
        $data = $admin->updateYaml('InvalidUnitTest.yml', ['now' => $now ]);
    }

}
