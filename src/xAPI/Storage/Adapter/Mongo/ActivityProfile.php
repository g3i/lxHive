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

use API\Storage\SchemaInterface;
use API\Storage\Query\ActivityProfileInterface;

use API\Controller;
use API\Util;
use API\Storage\Query\DocumentResult;
use API\Storage\Provider;

use API\Storage\AdapterException;

// TODO 0.11.x remove header dependency from this layer into parser and submit abstract args from there, like an array of options: put($data, $profileId, $agentIfi, array $options (contentType, if match))

class ActivityProfile extends Provider implements ActivityProfileInterface, SchemaInterface
{
    const COLLECTION_NAME = 'activityProfiles';

    /**
     * @var array $indexes
     *
     * @see https://docs.mongodb.com/manual/reference/command/createIndexes/
     *  [
     *      name: <index_name>,
     *      key: [
     *          <key-value_pair>,
     *          <key-value_pair>,
     *          ...
     *      ],
     *      <option1-value_pair>,
     *      <option1-value_pair>,
     *      ...
     *  ],
     */
    private $indexes = [
        //profileId is not unique as per spec, only combination of profileId and activityId
    ];

    /**
     * {@inheritDoc}
     */
    public function install()
    {
        $container = $this->getContainer()->get('storage');
        $container->executeCommand(['create' => self::COLLECTION_NAME]);
        $container->createIndexes(self::COLLECTION_NAME, $this->indexes);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * {@inheritDoc}
     */
    public function getFiltered($parameters)
    {
        $storage = $this->getContainer()->get('storage');
        $expression = $storage->createExpression();

        // Single activity state
        if (isset($parameters['profileId'])) {
            $expression->where('profileId', $parameters['profileId']);
            $expression->where('activityId', $parameters['activityId']);

            $cursorCount = $storage->count(self::COLLECTION_NAME, $expression);
            $this->validateCursorCountValid($cursorCount);

            $cursor = $storage->find(self::COLLECTION_NAME, $expression);

            $documentResult = new DocumentResult();
            $documentResult->setCursor($cursor);
            $documentResult->setIsSingle(true);
            $documentResult->setRemainingCount(1);
            $documentResult->setTotalCount(1);

            return $documentResult;
        }

        $expression->where('activityId', $parameters['activityId']);

        if (isset($parameters['since'])) {
            $since = Util\Date::dateStringToMongoDate($parameters['since']);
            $expression->whereGreaterOrEqual('mongoTimestamp', $since);
        }

        $cursor = $storage->find(self::COLLECTION_NAME, $expression);

        $documentResult = new DocumentResult();
        $documentResult->setCursor($cursor);
        $documentResult->setIsSingle(false);

        return $documentResult;
    }

    /**
     * {@inheritDoc}
     */
    public function post($parameters, $profileObject)
    {
        return $this->put($parameters, $profileObject);
    }

    /**
     * {@inheritDoc}
     */
    public function put($parameters, $profileObject)
    {
        // TODO optimise (upsert),
        $profileObject = (string)$profileObject;

        $storage = $this->getContainer()->get('storage');

        // Set up the body to be saved
        $activityProfileDocument = new \API\Document\Generic();

        // Check for existing state - then merge if applicable
        $expression = $storage->createExpression();
        $expression->where('profileId', $parameters['profileId']);
        $expression->where('activityId', $parameters['activityId']);

        $result = $storage->findOne(self::COLLECTION_NAME, $expression);
        if ($result) {
            $result = new \API\Document\Generic($result);
        }

        $ifMatchHeader = isset($parameters['headers']['if-match']) ? $parameters['headers']['if-match'] : null;
        $ifNoneMatchHeader = isset($parameters['headers']['if-none-match']) ? $parameters['headers']['if-none-match'] : null;
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        $contentType = $parameters['headers']['content-type'];
        if ($contentType === null) {
            $contentType = 'text/plain';
        } else {
            $contentType = $contentType[0];
        }

        // ID exists, try to merge body if applicable
        if ($result) {
            $this->validateDocumentType($result, $contentType);

            // This validation is still in arrays, which is why we decode to them
            // Since a validation engine change is upcoming, this is currently left as is
            $decodedExisting = json_decode($result->getContent(), true);
            $this->validateJsonDecodeErrors();

            $decodedPosted = json_decode($profileObject, true);
            $this->validateJsonDecodeErrors();

            $profileObject = json_encode(array_merge($decodedExisting, $decodedPosted));
            $activityProfileDocument = $result;
        }

        $activityProfileDocument->setContent($profileObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityProfileDocument->setActivityId($parameters['activityId']);
        $activityProfileDocument->setProfileId($parameters['profileId']);
        $activityProfileDocument->setContentType($contentType);
        $activityProfileDocument->setHash(sha1($profileObject));

        $storage->update(self::COLLECTION_NAME, $expression, $activityProfileDocument, true);

        return $activityProfileDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($parameters)
    {
        $storage = $this->getContainer()->get('storage');
        $expression = $storage->createExpression();

        $expression->where('profileId', $parameters['profileId']);
        $expression->where('activityId', $parameters['activityId']);

        $result = $storage->findOne(self::COLLECTION_NAME, $expression);

        $cursorCount = $storage->count(self::COLLECTION_NAME, $expression);

        $this->validateCursorCountValid($cursorCount);

        $ifMatchHeader = isset($parameters['headers']['if-match']) ? $parameters['headers']['if-match'] : null;
        $ifNoneMatchHeader = isset($parameters['headers']['if-none-match']) ? $parameters['headers']['if-none-match'] : null;

        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        $deletionResult = $storage->delete(self::COLLECTION_NAME, $expression);

        return $deletionResult;
    }

    private function validateMatchHeaders($ifMatch, $ifNoneMatch, $result)
    {
        // If-Match first
        $ifMatch = isset($ifMatch[0]) ? $ifMatch[0] : [];
        if ($ifMatch && $result && ($this->trimHeader($ifMatch) !== $result->getHash())) {
            throw new AdapterException('If-Match header doesn\'t match the current ETag.', Controller::STATUS_PRECONDITION_FAILED);
        }

        // Then If-None-Match
        $ifMatch = isset($ifNoneMatch[0]) ? $ifNoneMatch[0] : [];
        if ($ifNoneMatch) {
            if ($this->trimHeader($ifNoneMatch) === '*' && $result) {
                throw new AdapterException('If-None-Match header is *, but a resource already exists.', Controller::STATUS_PRECONDITION_FAILED);
            } elseif ($result && $this->trimHeader($ifNoneMatch) === $result->getHash()) {
                throw new AdapterException('If-None-Match header matches the current ETag.', Controller::STATUS_PRECONDITION_FAILED);
            }
        }
    }

    private function validateMatchHeaderExists($ifMatch, $ifNoneMatch, $result)
    {
        // Check If-Match and If-None-Match here
        if (!$ifMatch && !$ifNoneMatch && $result) {
            throw new AdapterException('There was a conflict. Check the current state of the resource and set the "If-Match" header with the current ETag to resolve the conflict.', Controller::STATUS_CONFLICT);
        }
    }

    private function validateDocumentType($document, $contentType)
    {
        if ($document->getContentType() !== 'application/json') {
            throw new AdapterException('Original document is not JSON. Cannot merge!', Controller::STATUS_BAD_REQUEST);
        }
        if ($contentType !== 'application/json') {
            throw new AdapterException('Posted document is not JSON. Cannot merge!', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateTargetDocumentType($document)
    {
        if ($document->getContentType() !== 'application/json') {
            throw new AdapterException('Original document is not JSON. Cannot merge!', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateSourceDocumentType($documentType)
    {
        if ($documentType !== 'application/json') {
            throw new AdapterException('Posted document is not JSON. Cannot merge!', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateCursorCountValid($cursorCount)
    {
        if ($cursorCount === 0) {
            throw new AdapterException('Agent profile does not exist.', Controller::STATUS_NOT_FOUND);
        }
    }

    private function validateJsonDecodeErrors()
    {
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AdapterException('Invalid JSON in existing document. Cannot merge!', Controller::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Trims quotes from the header.
     *
     * @param string $headerString Header
     *
     * @return string Trimmed header
     */
    private function trimHeader($headerString)
    {
        return trim($headerString, '"');
    }
}
