<?php
namespace Tests\API;

use Tests\TestCase;

use API\Container;
use API\ContainerException;
use API\BaseTrait;

class BasetraitTest extends TestCase
{
    use BaseTrait;

    public function testSetContainer()
    {
        $container = new Container();
        $container['pi'] = pi();
        $this->setContainer($container);

        $services = $this->getContainer();
        $this->assertEquals(pi(), $container->get('pi'));
    }

    public function testInvalidContainerType()
    {
        $threw = false;
        try {
            $services = $this->setContainer(array());
        } catch (\Throwable $t) {
            $threw = 'PHP 7.x';
        } catch (\Exception $e) {
            $threw = 'PHP 5.x';
        }
        $this->assertNotFalse($threw);
    }

    public function testNoContainer()
    {
        $this->expectException(\Exception::class);
        $services = $this->getContainer();
    }

    public function testNoStorageContainer()
    {
        $container = new Container();
        $this->setContainer($container);

        $this->expectException(ContainerException::class);
        $services = $this->getStorage();
    }

    public function testNoLogContainer()
    {
        $container = new Container();

        $this->setContainer($container);
        $this->expectException(ContainerException::class);
        $services = $this->getStorage();
    }
}
