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

namespace API\Storage;

interface AdapterInterface
{
    /**
     * Inserts the document into the specified collection.
     *
     * @param array $documents array of API\Document\DocumentInterface Documents to be inserted
     * @param string $collection name Mongo collection name
     *
     * @return DocumentResult The result of this query
     */
    public function insertMultiple($documents, $collection);

    /**
     * Inserts the document into the specified collection.
     *
     * @param API\Document\DocumentInterface $document The document to be inserted
     * @param string $collection Name of the collection to insert to
     *
     * @return DocumentResult The result of this query
     */
    public function insertOne($document, $collection);

    /**
     * Updates documents matching the filter.
     *
     * @param object|array $newDocument The document to be inserted
     * @param array $filter The query to update the documents
     * @param string $collection Name of collection
     *
     * @return DocumentResult The result of this query
     */
    public function update($newDocument, $filter, $collection);

    /**
     * Deletes documents.
     *
     * @param array $query The query that matches documents the need to be deleted
     * @param string $collection Name of collection
     * @return DeletionResult Result of deletion
     */
    public function delete($query, $collection);

    /**
     * Fetches documents.
     *
     * @param array $query The query to fetch the documents by
     * @param string $collection Name of collection
     * @param array $options
     * @return DocumentResult Result of fetch
     */
    public function find($query, $collection, $options = []);

    /**
     * Fetches documents.
     *
     * @param array $query The query to fetch the first document by
     * @param string $collection Name of collection
     * @param array $options
     * @return DocumentResult Result of fetch
     */
    public function findOne($query, $collection, $options = []);

    /**
     * Test mongo connection and return buildinfo
     * @see https://docs.mongodb.com/manual/reference/command/buildInfo/
     *
     * @param array|object $args command document
     * @return object|false buildinfo or false if connection failed
     */
    public static function testConnection($uri);

    /**
     * Get Statement storage
     *
     * @return API\Storage\Query\StatementInterface
     */
    public function getStatementStorage();

    /**
     * Get Attachment storage
     *
     * @return API\Storage\Query\AttachmentInterface
     */
    public function getAttachmentStorage();

    /**
     * Get User storage
     *
     * @return API\Storage\Query\UserInterface
     */
    public function getUserStorage();

    /**
     * Get Log storage
     *
     * @return API\Storage\Query\LogInterface
     */
    public function getLogStorage();

    /**
     * Get Activity storage
     *
     * @return API\Storage\Query\ActivityInterface
     */
    public function getActivityStorage();

    /**
     * Get ActivityState storage
     *
     * @return API\Storage\Query\ActivityStateInterface
     */
    public function getActivityStateStorage();

    /**
     * Get ActivityProfile storage
     *
     * @return API\Storage\Query\ActivityProfile
     */
    public function getActivityProfileStorage();

    /**
     * Get AgentProfile storage
     *
     * @return API\Storage\Query\AgentProfileInterface
     */
    public function getAgentProfileStorage();

    /**
     * Get BasicAuth storage
     *
     * @return API\Storage\Query\BasicAuthInterface
     */
    public function getBasicAuthStorage();

    /**
     * Get OAuth storage
     *
     * @return API\Storage\Query\OAuthInterface
     */
    public function getOAuthStorage();

    /**
     * Get OAuthClients storage
     *
     * @return API\Storage\Query\OAuthClientsInterface
     */
    public function getOAuthClientsStorage();

    /**
     * Get AuthScopes storage
     *
     * @return API\Storage\Query\AuthScopesInterface
     */
    public function getAuthScopesStorage();
}
