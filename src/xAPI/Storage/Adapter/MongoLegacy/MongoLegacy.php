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

namespace API\Storage\Adapter\MongoLegacy;

use API\Storage\Adapter\AdapterInterface;
use Sokil\Mongo\Client;

class MongoLegacy implements AdapterInterface
{
    protected $container;

    private $client;

    public function __construct($container)
    {
        $this->container = $container;
        $client = new Client($this->getContainer()['settings']['storage']['MongoLegacy']['host_uri']);
        $client->useDatabase($this->getContainer()['settings']['storage']['MongoLegacy']['db_name']);
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
    public function insert($document, $collection)
    {
        $collectionObject = $this->getClient()->getCollection($collection);
        $collectionObject->insert(['param' => 'value']);
    }

    /**
     * Updates documents matching the filter.
     *
     * @param object|array $document   The document to be inserted
     * @param array        $query      The query to update the documents
     * @param string       $collection Name of collection
     *
     * @return DocumentResult The result of this query
     */
    public function update($modifications, $query, $collection)
    {
        $collectionObject = $this->getClient()->getCollection($collection);
        $collection->update($query, $modifications);
    }

    /**
     * Deletes documents.
     *
     * @param array  $query      The query that matches documents the need to be deleted
     * @param string $collection Name of collection
     *
     * @return DeletionResult Result of deletion
     */
    public function delete($query, $collection)
    {
        $collectionObject = $this->getClient()->getCollection($collection);
        $collectionObject->deleteDocuments($query);
    }

    /**
     * Fetches documents.
     *
     * @param array  $query      The query to fetch the documents by
     * @param string $collection Name of collection
     *
     * @return DocumentResult Result of fetch
     */
    public function get($query, $collection)
    {
        $collectionObject = $this->getDocumentManager()->getCollection('statements');
        $cursor = $collectionObject->find();
        $cursor->query($query);

        return $cursor;
    }

    /**
     * Fetches documents.
     *
     * @param array  $query      The query to fetch the first document by
     * @param string $collection Name of collection
     *
     * @return DocumentResult Result of fetch
     */
    public function getOne($query, $collection)
    {
        $collectionObject = $this->getDocumentManager()->getCollection('statements');
        $cursor = $collectionObject->find();
        $cursor->query($query);
        $document = $cursor->findOne();

        return $document;
    }

    public function testConnection($uri)
    {
        $client = new Client($uri);
        try {
            $mongoVersion = $client->getDbVersion();
            $connectionSuccess = true;
        } catch (\MongoConnectionException $e) {
            $connectionSuccess = false;
        }

        return $connectionSuccess;
    }

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
