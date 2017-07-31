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

namespace API\Storage\Adapter;


use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;

use API\DocumentInterface;
use API\BaseTrait;
use API\Config;
use API\Storage\AdapterInterface;

use API\Storage\AdapterException;
use MongoDB\Driver\Exception\Exception as MongoException;

class Mongo implements AdapterInterface
{
    use BaseTrait;

    private $client;

    private $databaseName;

    /**
     * @var self::REQUIRED_DB_VERSION minimal mongo version
     * @see https://www.mongodb.com/support-policy
     */
    const REQUIRED_DB_VERSION = '3.0.0';

    /**
     * Set up driver and database
     * @constructor
     */
    public function __construct($container)
    {
        $this->setContainer($container);
        $client = new \MongoDB\Driver\Manager(Config::get(['storage', 'Mongo', 'host_uri']));
        $this->databaseName = Config::get(['storage', 'Mongo', 'db_name']);
        $this->client = $client;
    }

    /**
     * Inserts the document into the specified collection.
     *
     * @param string$collection Name of the collection to insert to
     * @param API\Document\DocumentInterface $document The document to be inserted
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
     * @param string $collection Name of the collection to insert to
     * @param array  $documents Collection of API\Document\DocumentInterface documents to be inserted
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

    /**
     * Update a document or create a new document when no document matches the query criteria.
     *
     * @param string $collection Name of the collection to insert to
     * @param array $filter The filter that determines which documents to update
     * @param object|array $newDocument The modified document to be written
     * @return DocumentResult The result of this query
     */
    public function upsert($collection, $filter, $newDocument)
    {
        return $this->update($collection, $filter, $newDocument, true);
    }

    /**
     * Upserts documents matching the filter.
     *
     * @param string $collection Name of collection
     * @param array $filter The filter that determines which documents to update
     * @param object|array $newDocument The modified document to be written
     * @param bool $upsert
     * @return DocumentResult The result of this query
     */
    public function update($collection, $filter, $newDocument, $upsert = false)
    {
        if ($filter instanceof Mongo\ExpressionInterface) {
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
     * @param string $collection Name of collection
     * @param array $filter The filter that matches documents the need to be deleted
     * @return DeletionResult Result of deletion
     */
    public function delete($collection, $filter)
    {
        if ($filter instanceof Mongo\ExpressionInterface) {
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
     * @param string $collection Name of collection
     * @param array|Expression $filter The filter to fetch the documents by
     * @param array $options
     * @return DocumentResult Result of fetch
     */
    public function find($collection, $filter = [], $options = [])
    {
        if ($filter instanceof Mongo\ExpressionInterface) {
            $filter = $filter->toArray();
        }

        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->getClient()->executeQuery($this->databaseName . '.' . $collection, $query);

        return $cursor;
    }

    /**
     * Fetches distinct documents.
     *
     * @param string $collection Name of collection
     * @param array|Expression $filter The filter to fetch the documents by
     * @param array $options
     * @return DocumentResult Result of fetch
     */
    public function distinct($collection, $field, $filter = [], $options = [])
    {
        // query doesn't accept empty array: [MongoDB\Driver\Exception\RuntimeException] "query" had the wrong type. Expected object or null, found array
        $command = new Command([
            'distinct' => $collection,
            'key' => $field,
            'query' => (empty($filter)) ? null : $filter,
            'options' => $options,
        ]);

        $cursor = $this->getClient()->executeCommand($this->databaseName, $command);

        return $cursor;
    }

    /**
     * Fetches documents.
     *
     * @param string $collection Name of collection
     * @param array|Expression $filter The filter to fetch the documents by
     * @param array $options
     * @return DocumentResult Result of fetch
     */
    public function findOne($collection, $filter, $options = [])
    {
        if ($filter instanceof Mongo\ExpressionInterface) {
            $filter = $filter->toArray();
        }
        $options = ['limit' => 1] + $options;
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->getClient()->executeQuery($this->databaseName . '.' . $collection, $query);
        $document = current($cursor->toArray());
        return ($document === false) ? null : $document;
    }

    /**
     * Count
     *
     * @param string $collection Name of collection
     * @param array|Expression $filter The filter to fetch the documents by
     * @param array $options
     * @return int
     */
    public function count($collection, $filter = [], $options = [])
    {
        if ($filter instanceof Mongo\ExpressionInterface) {
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
            throw new AdapterException('Count command did not return a numeric "n" value');
        }
        return (integer) $result->n;
    }

    /**
     * Create a Mongo Expression
     *
     * @return Mongo\Expression
     */
    public function createExpression()
    {
        $expression = new Mongo\Expression();
        return $expression;
    }

    ////
    // Admin Tools
    ////

    /**
     * Checks if a Mongo Command exists.
     * This operation is costly and shoudl only be called during admin/install routines
     * @param string $search Mongo command name
     *
     * @return bool
     */
    public function supportsCommand($search)
    {
        $cursor = $this->executeCommand(['listCommands' => 1]);
        $result = $cursor->toArray()[0];
        $cmds = $result->commands;

        return isset($cmds->{$search});
    }

    /**
     * Check if minimum requred Mongo version is installed
     * @param  string $available Use for unit tests only
     *
     * @return array with storage version info if compatible
     * @throws AdapterException if installed version is lower than required
     */
    public function verifyDatabaseVersion($available = null)
    {
        $available = ($available) ? $available : $this->getDatabaseversion();
        $required = self::REQUIRED_DB_VERSION;

        $msg = [
            'installed' => $available,
            'required' => $required,
        ];

        if(version_compare($available, $required) < 0) {
            throw new AdapterException('Database version mismatch: ' . json_encode($msg) . "\n".'Please upgrade your Database!');
        }

        return $msg;
    }

    /**
     * Perform a Mongo command.
     * @see http://php.net/manual/en/class.mongodb-driver-command.php
     * @see http://php.net/manual/en/mongodb-driver-manager.executecommand.php
     *
     * @param array|object $args command document
     * @return Cursor MondoDb cursor
     */
    public function executeCommand($args)
    {
        $command = new Command($args);
        $cursor = $this->getClient()->executeCommand($this->databaseName, $command);
        return $cursor;
    }

    /**
     * Create indexes for a collection
     * @param string $collection collection name, (will be autocreated)
     * @param array|object $indexes indexes to be created
     * @return Cursor|null MondoDb cursor
     */
    public function createIndexes($collection, $indexes)
    {

        if(empty($indexes)) {
            return null;
        }

        $args = [
            "createIndexes" => $collection,
            "indexes"       => $indexes,
        ];

        $cursor = $this->executeCommand($args);
        return $cursor;
    }

    // TODO: Maybe remove these methods and call them in their respective Service classes - these helpers are worthless here and only add extra complexity!
    /**
     * {@inheritDoc}
     */
    public function getStatementStorage()
    {
        $storage = new Mongo\Statement($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttachmentStorage()
    {
        $storage = new Mongo\Attachment($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserStorage()
    {
        $storage = new Mongo\User($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthScopesStorage()
    {
        $storage = new Mongo\AuthScopes($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getLogStorage()
    {
        $storage = new Mongo\Log($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getActivityStorage()
    {
        $storage = new Mongo\Activity($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getActivityStateStorage()
    {
        $storage = new Mongo\ActivityState($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getActivityProfileStorage()
    {
        $storage = new Mongo\ActivityProfile($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getAgentProfileStorage()
    {
        $storage = new Mongo\AgentProfile($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getBasicAuthStorage()
    {
        $storage = new Mongo\BasicAuth($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getOAuthStorage()
    {
        $storage = new Mongo\OAuth($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getOAuthClientsStorage()
    {
        $storage = new Mongo\OAuthClients($this->getContainer());
        return $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabaseversion()
    {
        $result = $this->executeCommand(['buildinfo' => 1]);

        // another option would tbe 'buildinfo.versionArray' property
        // however, it is not clear how widely this is supported
        return $result->toArray()[0]->version;
    }

    /**
     * {@inheritDoc}
     */
    public static function testConnection($uri)
    {
        $client = new \MongoDB\Driver\Manager($uri);
        $command = new \MongoDB\Driver\Command(['buildinfo' => 1]);
        $result = $client->executeCommand('admin', $command);

        if ($result) {
            $result = $result->toArray()[0];
        } else {
            $result = false;
        }

        return $result;
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
