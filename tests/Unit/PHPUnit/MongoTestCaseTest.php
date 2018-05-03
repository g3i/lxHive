<?php
namespace Tests\Unit\PHPUnit;
use Tests\MongoTestCase;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteResult;
use MongoDB\Driver\Exception\BulkWriteException;

class MongoTestCaseTest extends MongoTestCase
{

    private $collection = 'bulkwritetest';

    public function testBulkWrite()
    {
        $this->dropCollection($this->collection);

        $bulk = new BulkWrite();

        $bulk->insert(['_id' => 1, 'x' => 1]);
        $bulk->insert(['_id' => 2, 'x' => 2]);
        $bulk->update(['x' => 2], ['$set' => ['x' => 1]]);
        $bulk->insert(['_id' => 3, 'x' => 3]);
        $bulk->delete(['x' => 1]);

        $result = $this->bulkWrite($this->collection, $bulk);

        $this->assertInstanceOf(WriteResult::class, $result);
        $this->assertTrue($result->isAcknowledged());
        $this->assertNull($result->getWriteConcernError());
        $this->assertEmpty($result->getWriteErrors());

        $this->assertEquals($result->getDeletedCount(), 2); // incl update!
        $this->assertEquals($result->getInsertedCount(), 3);
        $this->assertEquals($result->getModifiedCount (), 1);
    }

    public function testBulkWriteError()
    {
        $this->dropCollection($this->collection);

        $bulk = new BulkWrite();

        $bulk->insert(['_id' => 1, 'x' => 1]);
        $bulk->insert(['_id' => 1, 'x' => 2]);

        $this->expectException(\Exception::class);
        $result = $this->bulkWrite($this->collection, $bulk);
    }
}
