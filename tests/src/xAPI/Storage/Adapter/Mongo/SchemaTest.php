<?php
namespace Tests\API\Storage\Adapter\Mongo;

use Tests\MongoTestCase;

use API\Bootstrap;
use API\Storage\Adapter\Mongo\Schema;


class SchemaTest extends MongoTestCase
{
    public function testMapCollections()
    {
        $schema = new Schema(Bootstrap::getContainer());
        $collections = $schema->mapCollections();
        $this->assertGreaterThan(1, count($collections));
        foreach($collections as $key => $value) {
            $this->assertTrue(is_string($key));
            $this->assertTrue(is_string($value));
            $this->assertTrue(!empty($key));
            $this->assertTrue(!empty($value));
        }
    }

    /**
     * @depends testMapCollections
     */
    public function testGetIndexes()
    {
        $schema = new Schema(Bootstrap::getContainer());

        $collections = $schema->mapCollections();
        $schemas = $schema->getIndexes();

        $collectionKeys = implode(',', array_keys($collections));
        $schemaKeys = implode(',', array_keys($schemas));

        $this->assertGreaterThan(1, count($schemas));
        $this->assertEquals($collectionKeys, $schemaKeys);

    }

    /**
     * @depends testGetIndexes
     */
    public function testInstallIndexes()
    {
        // drop database
        $this->dropDatabase();

        $schema = new Schema(Bootstrap::getContainer());
        $schema->install();
        // passed if no exception are thrown by MongoDriver
        $this->assertTrue(true, 'No exception was thrown');

    }
}
