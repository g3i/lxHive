<?php
namespace Tests\Unit\PHPUnit;
use Tests\TestCase;

use API\Container as ApiContainer;
use Slim\Container as SlimContainer;

class CoherentContainersTest extends TestCase
{
    public function testSlimContainerInteropInterfaceHandle()
    {
        $container = new SlimContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->get('testService'));
    }

    public function testApiContainerInteropInterfaceHandle()
    {
        $container = new ApiContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->get('testService'));
    }

    /**
     * @depends testSlimContainerInteropInterfaceHandle
     */
    public function testSlimContainerObjectHandle()
    {
        $container = new SlimContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->testService);
    }

    /**
     * @depends testSlimContainerInteropInterfaceHandle
     */
    public function testApiContainerObjectHandle()
    {
        $container = new ApiContainer();
        $container['testService'] = 'string';
        $this->assertEquals('string', $container->testService);
    }
}
