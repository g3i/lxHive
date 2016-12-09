<?php

use Slim\Slim;

class StatementRefs extends \Sokil\Mongo\Migrator\AbstractMigration
{
    public function up()
    {
    	// Add references
        $slimInstance = Slim::getInstance();
        $collection   = $slimInstance->mongo->getCollection('statements');
        $cursor       = $collection->find();
        foreach ($cursor as $statementDocument) {
        	$this->addReferences($statementDocument);
        }
    }

    private function addReferences($statementDocument)
    {
    	try {
	    	if ($statementDocument->isReferencing()) {
	 
	    		$referencedStatement = $statementDocument->getReferencedStatement();

	            if ($referencedStatement->isReferencing()) {
	            	$this->addReferences($referencedStatement);
	            }

	            $existingReferences = [];
	            if (null !== $referencedStatement->getReferences()) {
	                $existingReferences = $referencedStatement->getReferences();
	            }
	            $existingReferences[] = $referencedStatement->getStatement();
	            $statementDocument->setReferences($existingReferences);
	        	$statementDocument->save();
	        }
		} catch (\Exception $e) {
			return false;
		}
    }
    
    public function down()
    {
        // Remove references
        $slimInstance = Slim::getInstance();
        $collection   = $slimInstance->mongo->getCollection('statements');
        $cursor       = $collection->find();
        foreach ($cursor as $statementDocument) {
        	$this->removeReferences($statementDocument);
        }
    }

    private function removeReferences($statementDocument)
    {
    	if ($statementDocument->isReferencing()) {
            $referencedStatement = $statementDocument->getReferencedStatement();

            if ($referencedStatement->isReferencing()) {
            	$this->removeReferences($referencedStatement);
            }

            $noReferences = [];
            $statementDocument->setReferences($noReferences);
        	$statementDocument->save();
        }
    }
}