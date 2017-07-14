<?php
namespace Tests\Unit\PHPUnit;

use Tests\TestCase;
use API\Config;

class BootstrapUnitTestDatabaseTest extends TestCase
{
    public function testPhpUnitXMLConstant()
    {
        $this->assertTrue(defined('LXHIVE_UNITTEST'));
    }

    public function testConfigHasUniTestDatabase()
    {
       $db_name = Config::get(['storage', 'Mongo', 'db_name']);
       $this->assertEquals($db_name, 'LXHIVE_UNITTEST');
    }
}
