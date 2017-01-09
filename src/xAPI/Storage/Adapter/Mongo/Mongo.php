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

class Mongo implements AdapterInterface
{
    protected $container;

    private $client;

    private $databaseName;

    public function __construct($container)
    {
        $this->container = $container;
        $client = new MongoDB\Driver\Manager($this->getContainer()['settings']['storage']['Mongo']['host_uri']);
        $this->databaseName = $this->getContainer()['settings']['storage']['Mongo']['db_name'];
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
        $bulk = new MongoDB\Driver\BulkWrite();
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
        $bulk = new MongoDB\Driver\BulkWrite();
        foreach ($documents as $document) {
            $bulk->insert($document);
        }

        $result = $this->getClient()->executeBulkWrite($this->databaseName . '.' . $collection, $bulk);
        return $result;
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
    public function update($collection, $filter, $newDocument)
    {
        if ($filter instanceof ExpressionInterface) {
            $filter = $filter->toArray();
        }
        $collectionObject = $this->getClient()->getCollection($collection);
        $collection->update($filter, $newDocument);

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update($document);

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
        $bulk = new MongoDB\Driver\BulkWrite();
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
    public function find($collection, $filter, $options)
    {
        if ($filter instanceof ExpressionInterface) {
            $filter = $filter->toArray();
        }
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $mongo->executeQuery($this->databaseName . '.' . $collection, $query);
        
        return $cursor;
    }

    public function createExpression()
    {
        $expression = new Expression();
        return $expression;
    }

    public static function testConnection($uri)
    {
        $client = new MongoDB\Driver\Manager($uri);
        $buildInfoCommand = new MongoDB\Driver\Command(['buildinfo' => 1]);
        $result = $client->executeCommand('admin', $buildInfoCommand);
        
        if ($result) {
            $result = $result->toArray()[0];
            $result = $result['version'];
        } else {
            $result = false;
        }
        
        return $result;
    }

    // TODO: Maybe remove these methods and call them in their respective Service classes - these helpers are worthless here and only add extra complexity!
    public static function getStatementStorage($container)
    {
        $statementStorage = new Statement($container());

        return $statementStorage;
    }

    public function getAttachmentStorage()
    {
        $attachmentStorage = new Attachment($container);

        return $attachmentStorage;
    }

    public function getUserStorage()
    {
        $userStorage = new User($container);

        return $userStorage;
    }

    public function getLogStorage()
    {
        $logStorage = new Log($container);

        return $logStorage;
    }

    public function getActivityStorage()
    {
        $activityStorage = new Activity($container);

        return $activityStorage;
    }

    public function getActivityStateStorage()
    {
        $activityStateStorage = new ActivityState($container);

        return $activityStateStorage;
    }

    public function getActivityProfileStorage()
    {
        $activityProfileStorage = new ActivityProfile($container);

        return $activityProfileStorage;
    }

    public function getAgentProfileStorage()
    {
        $agentProfileStorage = new AgentProfile($container);

        return $agentProfileStorage;
    }

    public function getBasicAuthStorage()
    {
        $agentProfileStorage = new BasicAuth($container);

        return $agentProfileStorage;
    }

    public function getOAuthStorage()
    {
        $agentProfileStorage = new OAuth();

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
