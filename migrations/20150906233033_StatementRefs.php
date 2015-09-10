<?php

use Slim\Slim;
use API\Service\Statement as StatementService;

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
    	if ($statementDocument->isReferencing()) {
            $referencedStatement = $statementDocument->getReferencedStatement();

            if ($referencedStatement->isReferencing()) {
            	$this->addReferences($referencedStatement->getReferencedStatement());
            }

            $existingReferences = [];
            if (null !== $referencedStatement->getReferences()) {
                $existingReferences = $referencedStatement->getReferences();
            }
            $statementDocument->setReferences(array_push($existingReferences, $referencedStatement->getStatement()));
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
        	$this->removeReferences($statementDocument);
        }
    }

    private function removeReferences($statementDocument)
    {
    	if ($statementDocument->isReferencing()) {
            $referencedStatement = $statementDocument->getReferencedStatement();

            if ($referencedStatement->isReferencing()) {
            	$this->removeReferences($referencedStatement->getReferencedStatement());
            }

            $noReferences = [];
            $statementDocument->setReferences($noReferences);
        	$statementDocument->save();
        }
    }
}