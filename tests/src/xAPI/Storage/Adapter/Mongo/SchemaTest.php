<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\TestCase;

use API\Container;
use API\Storage\Adapter\Mongo\Schema;


class SchemaTest extends TestCase
{
    public function testMapCollections()
    {
        $container = new Container();
        $schema = new Schema($container);
        $collections = $schema->mapCollections();
        $this->assertGreaterThan(1, count($collections));
        foreach($collections as $key => $value) {
            $this->assertTrue(is_string($key));
            $this->assertTrue(is_string($value));
            $this->assertTrue(!empty($key));
            $this->assertTrue(!empty($value));
        }
    }
}
