<?php

namespace Tests\API;

use Tests\TestCase;
use API\Bootstrap;

class StorageTypeTest extends TestCase
{
	const MOCK_STATEMENT = '{"actor":{"objectType":"Agent","name":"Buster Keaton","mbox":"mailto:buster@keaton.com"},"verb":{"id":"http://adlnet.gov/expapi/verbs/voided","display":{"en-US":"voided"}},"object":{"objectType":"StatementRef","id":"{{statementId}}"}}';
	const MOCK_COLLECTION = 'tests';

    protected function setUp()
    {
    	// Maybe move Bootstrap to here if there will be more tests
    }

	public function testObjectReturned()
    {
    	$bootstrap = Bootstrap::factory(Bootstrap::Testing);
    	$testContainer = $bootstrap->bootTest();
    	$storage = $testContainer['storage'];
    	
    	// Another option would be to directly instantiate API\Storage\Adapter\Mongo
    	// and only call Boostrap::Config for the mode (so we'd have the Config API)
    	
    	// Check object insertion works well
    	$objectToInsert = json_decode(self::MOCK_STATEMENT);
    	$this->assertInstanceOf('stdClass', $objectToInsert);
    	
    	$storage->insertOne(self::MOCK_COLLECTION, $objectToInsert);

    	// Check objects are returned
    	$this->assertInstanceOf('stdClass', $storage->find(self::MOCK_COLLECTION)->toArray()[0]);

    	$storage->delete(self::MOCK_COLLECTION, []);
    }

	
}