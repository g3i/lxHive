<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 Brightcookie Pty Ltd
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with lxHive. If not, see <http://www.gnu.org/licenses/>.
 *
 * For authorship information, please view the AUTHORS
 * file that was distributed with this source code.
 */

namespace API\Storage\Adapter\Mongo;

use API\Storage\Adapter\AdapterInterface;
use MongoDB\Driver\Command;
use API\Document\DocumentInterface;
use API\BaseTrait;

class Mongo implements AdapterInterface
{
    use BaseTrait;

    private $client;

    private $databaseName;

    public function __construct($container)
    {
        $this->setContainer($container);
        $client = new \MongoDB\Driver\Manager($this->getConfig()->get('storage.Mongo.host_uri'));
        $this->databaseName = $this->getConfig()->get('storage.Mongo.db_name');
        $this->client = $client;
    }

    /**
     * Inserts the document into the specified collection.
     *
     * @param API\Document\DocumentInterface $document   The document to be inserted
     * @param string                         $collection Name of the collection to insert to
     *
     * @return DocumentResult The result of this query
     */
    public function insertOne($collection, $document)
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        if ($document instanceof DocumentInterface) {
            $document = $document->toArray();
        }
        $bulk->insert($document);

        $result = $this->getClient()->executeBulkWrite($this->databaseName . '.' . $collection, $bulk);
        return $result;
    }

    /**
     * Inserts the document into the specified collection.
     *
     * @param API\Document\DocumentInterface $document   The document to be inserted
     * @param string                         $collection Name of the collection to insert to
     *
     * @return DocumentResult The result of this query
     */
    public function insertMultiple($collection, $documents)
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        foreach ($documents as $document) {
            if ($document instanceof DocumentInterface) {
                $document = $document->toArray();
            }
            $bulk->insert($document);
        }

        $result = $this->getClient()->executeBulkWrite($this->databaseName . '.' . $collection, $bulk);
        return $result;
    }

    public function upsert($collection, $filter, $newDocument)
    {
        return $this->update($collection, $filter, $newDocument, true);
    }

    /**
     * Updates documents matching the filter.
     *
     * @param object|array $newDocument   The modified document to be written
     * @param array        $filter      The filter that determines which documents to update
     * @param string       $collection Name of collection
     *
     * @return DocumentResult The result of this query
     */
    public function update($collection, $filter, $newDocument, $upsert = false)
    {
        if ($filter instanceof ExpressionInterface) {
            $filter = $filter->toArray();
        }

        $bulk = new \MongoDB\Driver\BulkWrite();
        if ($newDocument instanceof DocumentInterface) {
            $newDocument = $newDocument->toArray();
        }
        $updateOptions = ['upsert' => $upsert];

        $bulk->update($filter, $newDocument, $updateOptions);

        $result = $this->getClient()->executeBulkWrite($this->databaseName . '.' . $collection, $bulk);
        return $result;
    }

    /**
     * Deletes documents.
     *
     * @param array  $filter      The filter that matches documents the need to be deleted
     * @param string $collection Name of collection
     *
     * @return DeletionResult Result of deletion
     */
    public function delete($collection, $filter)
    {
        if ($filter instanceof ExpressionInterface) {
            $filter = $filter->toArray();
        }
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->delete($filter);

        $result = $this->getClient()->executeBulkWrite($this->databaseName . '.' . $collection, $bulk);
        return $result;
    }

    /**
     * Fetches documents.
     *
     * @param array|Expression  $filter      The filter to fetch the documents by
     * @param string $collection Name of collection
     *
     * @return DocumentResult Result of fetch
     */
    public function find($collection, $filter = [], $options = [])
    {
        if ($filter instanceof ExpressionInterface) {
            $filter = $filter->toArray();
        }
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->getClient()->executeQuery($this->databaseName . '.' . $collection, $query);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        return $cursor;
    }

    /**
     * Fetches documents.
     *
     * @param array|Expression  $filter      The filter to fetch the documents by
     * @param string $collection Name of collection
     *
     * @return DocumentResult Result of fetch
     */
    public function findOne($collection, $filter, $options = [])
    {
        if ($filter instanceof ExpressionInterface) {
            $filter = $filter->toArray();
        }
        $options = ['limit' => 1] + $options;
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->getClient()->executeQuery($this->databaseName . '.' . $collection, $query);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        $document = current($cursor->toArray());
        return ($document === false) ? null : $document;
    }

    public function count($collection, $filter = [], $options = [])
    {
        if ($filter instanceof ExpressionInterface) {
            $filter = $filter->toArray();
        }
        $command = ['count' => $collection];
        if (!empty($filter)) {
            $command['query'] = $filter;
        }

        foreach (['hint', 'limit', 'maxTimeMS', 'skip'] as $option) {
            if (isset($options[$option])) {
                $command[$option] = $options[$option];
            }
        }

        $command =  new Command($command);

        $cursor = $this->getClient()->executeCommand($this->databaseName, $command);
        $result = current($cursor->toArray());
        
        // Older server versions may return a float
        if (!isset($result->n) || ! (is_integer($result->n) || is_float($result->n))) {
            throw new Exception('Count command did not return a numeric "n" value');
        }
        return (integer) $result->n;
    }

    public function createExpression()
    {
        $expression = new Expression();
        return $expression;
    }

    public static function testConnection($uri)
    {
        $client = new \MongoDB\Driver\Manager($uri);
        $buildInfoCommand = new \MongoDB\Driver\Command(['buildinfo' => 1]);
        $result = $client->executeCommand('admin', $buildInfoCommand);
        $result->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        if ($result) {
            $result = $result->toArray()[0];
            $result = $result['version'];
        } else {
            $result = false;
        }
        
        return $result;
    }

    // TODO: Maybe remove these methods and call them in their respective Service classes - these helpers are worthless here and only add extra complexity!
    public function getStatementStorage()
    {
        $statementStorage = new Statement($this->getContainer());

        return $statementStorage;
    }

    public function getAttachmentStorage()
    {
        $attachmentStorage = new Attachment($this->getContainer());

        return $attachmentStorage;
    }

    public function getUserStorage()
    {
        $userStorage = new User($this->getContainer());

        return $userStorage;
    }

    public function getLogStorage()
    {
        $logStorage = new Log($this->getContainer());

        return $logStorage;
    }

    public function getActivityStorage()
    {
        $activityStorage = new Activity($this->getContainer());

        return $activityStorage;
    }

    public function getActivityStateStorage()
    {
        $activityStateStorage = new ActivityState($this->getContainer());

        return $activityStateStorage;
    }

    public function getActivityProfileStorage()
    {
        $activityProfileStorage = new ActivityProfile($this->getContainer());

        return $activityProfileStorage;
    }

    public function getAgentProfileStorage()
    {
        $agentProfileStorage = new AgentProfile($this->getContainer());

        return $agentProfileStorage;
    }

    public function getBasicAuthStorage()
    {
        $agentProfileStorage = new BasicAuth($this->getContainer());

        return $agentProfileStorage;
    }

    public function getOAuthStorage()
    {
        $agentProfileStorage = new OAuth($this->getContainer());

        return $agentProfileStorage;
    }

    /**
     * Gets the value of container.
     *
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets the value of container.
     *
     * @param mixed $container the container
     *
     * @return self
     */
    protected function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Gets the value of client.
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }
}
