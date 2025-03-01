<?php
namespace Tests\Unit\PHPUnit;
use Tests\TestCase;

use API\Container as ApiContainer;
use Slim\Container as SlimContainer;

class CoherentContainersTest extends TestCase
{
    public function testSlimContainerInterfaceHandle()
    {
        $container = new SlimContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->get('testService'));
    }

    public function testApiContainerInterfaceHandle()
    {
        $container = new ApiContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->get('testService'));
    }

    /**
     * @depends testSlimContainerInterfaceHandle
     */
    public function testSlimContainerObjectHandle()
    {
        $container = new SlimContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->testService);
    }

    /**
     * @depends testSlimContainerInterfaceHandle
     * @expectedException PHPUnit_Framework_Error
     */
    public function testApiContainerObjectHandle()
    {
        $container = new ApiContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->testService);
    }
}
