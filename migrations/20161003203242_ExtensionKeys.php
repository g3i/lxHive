<?php

use Slim\Slim;

class ExtensionKeys extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
        // Add references
        $slimInstance = Slim::getInstance();
        $collection   = $slimInstance->mongo->getCollection('statements');
        $cursor       = $collection->find();
        foreach ($cursor as $statementDocument) {
        	$statementDocument->convertExtensionKeysToUnicode();
        	$statementDocument->save();
        }
    }
    
    public function down()
    {
        // Remove references
        $slimInstance = Slim::getInstance();
        $collection   = $slimInstance->mongo->getCollection('statements');
        $cursor       = $collection->find();
        foreach ($cursor as $statementDocument) {
        	$statementDocument->convertExtensionKeysFromUnicode();
        	$statementDocument->save();
        }
    }
}