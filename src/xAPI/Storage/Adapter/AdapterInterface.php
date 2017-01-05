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

interface AdapterInterface
{
    /**
     * Inserts the document into the specified collection.
     *
     * @param API\Document\DocumentInterface $document   The document to be inserted
     * @param string                         $collection Name of the collection to insert to
     *
     * @return DocumentResult The result of this query
     */
    public function insertMultiple($documents, $collection);

    /**
     * Inserts the document into the specified collection.
     *
     * @param API\Document\DocumentInterface $document   The document to be inserted
     * @param string                         $collection Name of the collection to insert to
     *
     * @return DocumentResult The result of this query
     */
    public function insert($document, $collection);

    /**
     * Updates documents matching the filter.
     *
     * @param object|array $document   The document to be inserted
     * @param array        $query      The query to update the documents
     * @param string       $collection Name of collection
     *
     * @return DocumentResult The result of this query
     */
    public function update($newDocument, $filter, $collection);

    /**
     * Deletes documents.
     *
     * @param array  $query      The query that matches documents the need to be deleted
     * @param string $collection Name of collection
     *
     * @return DeletionResult Result of deletion
     */
    public function delete($query, $collection);

    /**
     * Fetches documents.
     *
     * @param array  $query      The query to fetch the documents by
     * @param string $collection Name of collection
     *
     * @return DocumentResult Result of fetch
     */
    public function get($query, $collection);

    /**
     * Fetches documents.
     *
     * @param array  $query      The query to fetch the first document by
     * @param string $collection Name of collection
     *
     * @return DocumentResult Result of fetch
     */
    public function getOne($query, $collection);

    public function testConnection($uri);

    public function getStatementStorage();

    public function getAttachmentStorage();

    public function getUserStorage();

    public function getLogStorage();

    public function getActivityStorage();

    public function getActivityStateStorage();

    public function getActivityProfileStorage();

    public function getAgentProfileStorage();

    public function getBasicAuthStorage();

    public function getOAuthStorage();
}
