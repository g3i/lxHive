<?php
namespace Tests\API;

use Tests\TestCase;

use API\Bootstrap;
use API\Config;
use API\AppInitException;
use InvalidArgumentException;

class ConfigTest extends TestCase
{

    protected function setUp(): void
    {
        Bootstrap::factory(Bootstrap::None);
        Bootstrap::reset();
    }

    public function testFactorySingletion()
    {
        Config::factory([]);

        $this->expectException(AppInitException::class);
        Config::factory([]);
    }

    /**
     * @depends testFactorySingletion
     */
    public function testWithoutFactorySettersAndGettersThrowException()
    {
        $this->expectException(AppInitException::class);
        Config::merge(['a' => 'value']);
        Config::set('a', 'value');
        Config::all('a');
        Config::get('a');
    }

    /**
     * This test inspects only the behavioural intersection between API/Config and API/Bootstrap
     * API/Bootstrap covered in depth with it's own tests
     *
     * @depends testFactorySingletion
     */
    public function testFactoryResetByBootstrap()
    {
        Config::factory([]);

        Bootstrap::factory(Bootstrap::None);
        Bootstrap::reset();
        Config::factory([]);

        $this->expectException(AppInitException::class);
        Bootstrap::factory(Bootstrap::Web);
        Config::factory([]);
    }

    /**
     * @depends testFactoryResetByBootstrap
     */
    public function testFactoryRequiresArrayData()
    {
        Bootstrap::reset();
        Config::factory([]);

        Bootstrap::reset();
        Config::factory(['a' => 'value']);

        Bootstrap::reset();
        Config::factory([1]);

        Bootstrap::reset();
        $this->expectException(InvalidArgumentException::class);
        Config::factory('invalid');

        Bootstrap::reset();
        $this->expectException(InvalidArgumentException::class);
        Config::factory(null);

        Bootstrap::reset();
        $this->expectException(InvalidArgumentException::class);
        Config::factory(json_decode('{ "an": "object" }'));
    }

    public function testGetSingleKey()
    {
        $now = time();

        Config::factory(['now' => $now]);
        $this->assertEquals(Config::get('now'), $now);

        $this->assertEquals(Config::get(0), null);
        $this->assertEquals(Config::get('something'), null);

        $this->assertEquals(Config::get('something', 'custom'), 'custom');
        $this->assertEquals(Config::get(0, 1), 1);
    }

    /**
     * @depends testGetSingleKey
     */
    public function testGetArrayKey()
    {
        $arr = [
            'A' => [
                'B' => 'b',
                1,
            ],
            'B' => 'B',
        ];
        Config::factory($arr);

        // instance
        $this->assertTrue(Config::get('A') == $arr['A']);

        // empty array gets all
        $this->assertEquals(Config::get([]), $arr);

        // single array element
        $this->assertEquals(Config::get(['B']), 'B');

        // nested
        $this->assertEquals(Config::get(['A', 'B']), 'b');
        $this->assertEquals(Config::get(['A', 0]), 1);

        // nested non-exist
        $this->assertEquals(Config::get(['A', 'B', 'C']), null);
        $this->assertEquals(Config::get(['A', 'B', 'C'], 'c'), 'c');
    }

    public function testAll()
    {
        $arr = [
            'A' => [
                'B' => 'b',
                1,
            ],
            'B' => 'B',
        ];
        Config::factory($arr);

        // instance
        $this->assertTrue(Config::all() === $arr);
    }

    /**
     * @depends testGetArrayKey
     */
    public function testMerge()
    {
        $now = time();
        Config::factory(['now' => $now]);

        Config::merge(['a' => 'value']);
        Config::merge([1]);

        $this->assertEquals(Config::get('now'), $now);
        $this->assertEquals(Config::get('a'), 'value');
        $this->assertEquals(Config::get(0), 1);

        $this->expectException(InvalidArgumentException::class);
        Config::merge(['a' => 'changed value']);
        Config::merge([1]);
    }

    public function testMergeRequiresArrayData()
    {
        Config::factory([]);

        $this->expectException(InvalidArgumentException::class);
        Config::merge('invalid');
        $this->expectException(InvalidArgumentException::class);
        Config::merge(null);
        $this->expectException(InvalidArgumentException::class);
        Config::merge(json_decode('{ "an": "object" }'));
    }

    public function testSet()
    {
        $now = time();
        $later = $now + 3600;
        Config::factory(['now' => $now]);

        Config::set('later', $later);
        $this->assertEquals(Config::get('later'), $later);
    }

    /**
     * @depends testSet
     */
    public function testSetCannotOverwriteExistingKey()
    {
        Config::factory([]);

        Config::set('A', 'a');
        $this->expectException(InvalidArgumentException::class);
        Config::set('A', 'a');
    }

    public function testSetValueCanBeAnything()
    {
        Config::factory([]);

        Config::set('object',json_decode('{ "an": "object" }'));
        $this->assertTrue(is_object(Config::get('object')));
    }

    public function testReset()
    {
        // @see testFactoryResetByBootstrap,
        $this->assertTrue(true, 'It was tested before');
    }

}
