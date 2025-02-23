<?php
namespace Tests\API;

use Tests\TestCase;

use API\Container;
use API\ContainerException;
use Psr\Container\ContainerInterface;

class ContainerTest extends TestCase
{
    public function testGetValue()
    {
        $container = new Container();
        $container['pi'] = pi();
        $this->assertEquals(pi(), $container->get('pi'));
    }

    public function testGetService()
    {
        $container = new Container();
        $container['DateService'] = new \DateTime();
        $this->assertInstanceOf('\DateTime', $container->get('DateService'));
    }


    public function testFrozenPimpleContainer()
    {
        $container = new \Pimple\Container();
        $container['DateService'] = function () {
            return 'string';
        };
        $this->assertEquals('string', $container['DateService']);

        $this->expectException(\RuntimeException::class);
        $container['DateService'] = function () {
            new \DateTime();
        };
        $container['DateService'] = 'another string';
    }

    public function testLockedContainer()
    {
        $container = new Container();
        $container['DateService'] = 'string';
        $this->assertEquals('string', $container->get('DateService'));

        $container['DateService'] = new \DateTime();
        $this->assertInstanceOf('\DateTime', $container->get('DateService'));

        $container->lock();

        $this->expectException(ContainerException::class);
        $container['DateService'] = 'another string';
    }

    public function testGetNotFoundWithException()
    {
        $this->expectException(ContainerException::class);
        $container = new Container();
        $container->get('foo');
    }

    public function testGetNotFoundWithReturnArg()
    {
        $container = new Container();
        $this->assertEquals('bar', $container->get('foo', 'bar'));
    }

    /**
     * Test `get()` throws  a ContainerExpception - when there is a DI config error
     */
    public function testGetWithDiConfigErrorThrownAsContainerValueNotFoundException()
    {
        $this->expectException(ContainerException::class);
        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) {
                return $container->get('doesnt-exist');
            }
        ;
        $container->get('foo');
    }

    /**
     * Test `get()` does not recast exceptions which are thrown in a factory closure
     */
    public function testGetWithErrorThrownByFactoryClosure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $invokable = $this->getMockBuilder('StdClass')->setMethods(['__invoke'])->getMock();
        /** @var \Callable $invokable */
        $invokable->expects($this->any())
            ->method('__invoke')
            ->will($this->throwException(new \InvalidArgumentException()));

        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) use ($invokable) {
                call_user_func($invokable);
            }
        ;
        $container->get('foo');
    }
}
