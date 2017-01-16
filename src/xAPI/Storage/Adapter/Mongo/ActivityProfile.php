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

use API\Resource;
use API\HttpException as Exception;
use API\Storage\Query\DocumentResult;

class ActivityProfile extends Base implements ActivityProfileInterface
{
    public function getActivityProfilesFiltered($parameters)
    {
        $storage = $this->getContainer()['storage'];
        $collection = 'activityProfiles';
        $expression = $storage->createExpression();

        // Single activity state
        if (isset($parameters['profileId'])) {
            $expression->where('profileId', $parameters['profileId']);
            $expression->where('activityId', $parameters['activityId']);

            $cursorCount = $storage->count($collection, $expression);
            $this->validateCursorCountValid($cursorCount);

            $cursor = $storage->find($collection, $expression);

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

        $cursor = $storage->find($collection, $expression);

        $documentResult = new DocumentResult();
        $documentResult->setCursor($cursor);
        $documentResult->setIsSingle(false);

        return $documentResult;
    }

    public function postActivityProfile($parameters, $profileObject)
    {
        $storage = $this->getContainer()['storage'];
        $collection = 'activityProfiles';

        // Set up the body to be saved
        $activityProfileDocument = new \API\Document\Generic();

        // Check for existing state - then merge if applicable
        $expression = $storage->createExpression();
        $expression->where('profileId', $parameters['profileId']);
        $expression->where('activityId', $parameters['activityId']);

        $result = $storage->findOne($collection, $expression);
        $result = new \API\Document\Generic($result);

        $ifMatchHeader = $parameters['headers']['If-Match'];
        $ifNoneMatchHeader = $parameters['headers']['If-None-Match'];
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        $contentType = $parameters['headers']['Content-Type'];
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        // ID exists, try to merge body if applicable
        if ($result) {
            $this->validateDocumentType($result, $contentType);

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

        $storage->update($collection, $expression, $activityProfileDocument, true);

        // Add to log
        //$this->getContainer()->requestLog->addRelation('activityProfiles', $activityProfileDocument)->save();

        return $activityProfileDocument;
    }

    public function putActivityProfile($parameters, $profileObject)
    {
        $collection = $this->getDocumentManager()->getCollection('activityProfiles');

        $activityProfileDocument = $collection->createDocument();

        // Check for existing state - then replace if applicable
        $cursor = $collection->find();
        $cursor->where('profileId', $parameters['profileId']);
        $cursor->where('activityId', $parameters['activityId']);

        $result = $cursor->findOne();

        $ifMatchHeader = $parameters['headers']['If-Match'];
        $ifNoneMatchHeader = $parameters['headers']['If-None-Match'];

        $this->validateMatchHeaderExists($ifMatchHeader, $ifNoneMatchHeader, $result);
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        // ID exists, replace body
        if ($result) {
            $activityProfileDocument = $result;
        }

        $contentType = $parameters['headers']['Content-Type'];
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        $activityProfileDocument->setContent($profileObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityProfileDocument->setActivityId($parameters['activityId']);
        $activityProfileDocument->setProfileId($parameters['profileId']);
        $activityProfileDocument->setContentType($contentType);
        $activityProfileDocument->setHash(sha1($profileObject));
        $activityProfileDocument->save();

        // Add to log
        $this->getContainer()->requestLog->addRelation('activityProfiles', $activityProfileDocument)->save();

        return $activityProfileDocument;
    }

    public function deleteActivityProfile($parameters)
    {
        $collection = $this->getDocumentManager()->getCollection('activityProfiles');
        $cursor = $collection->find();

        $cursor->where('profileId', $parameters['profileId']);
        $cursor->where('activityId', $parameters['activityId']);

        $result = $cursor->findOne();

        $cursorCount = $cursor->count();

        $this->validateCursorCountValid($cursorCount);

        $ifMatchHeader = $parameters['headers']['If-Match'];
        $ifNoneMatchHeader = $parameters['headers']['If-None-Match'];

        $this->validateMatchHeaders($ifMatchH->geader, $ifNoneMatchHeader, $result);

        // Add to log
        $this->getContainer()->requestLog->addRelation('activityProfiles', $result)->save();

        $result->delete();
    }

    private function validateMatchHeaders($ifMatch, $ifNoneMatch, $result)
    {
        // If-Match first
        if ($ifMatch && $result && ($this->trimHeader($ifMatch) !== $result->getHash())) {
            throw new Exception('If-Match header doesn\'t match the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
        }

        // Then If-None-Match
        if ($ifNoneMatch) {
            if ($this->trimHeader($ifNoneMatch) === '*' && $result) {
                throw new Exception('If-None-Match header is *, but a resource already exists.', Resource::STATUS_PRECONDITION_FAILED);
            } elseif ($result && $this->trimHeader($ifNoneMatch) === $result->getHash()) {
                throw new Exception('If-None-Match header matches the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
            }
        }
    }

    private function validateMatchHeaderExists($ifMatch, $ifNoneMatch, $result)
    {
        // Check If-Match and If-None-Match here
        if (!$ifMatch && !$ifNoneMatch && $result) {
            throw new Exception('There was a conflict. Check the current state of the resource and set the "If-Match" header with the current ETag to resolve the conflict.', Resource::STATUS_CONFLICT);
        }
    }

    private function validateDocumentType($document, $contentType)
    {
        if ($document->getContentType() !== 'application/json') {
            throw new Exception('Original document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
        if ($contentType !== 'application/json') {
            throw new Exception('Posted document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
    }

    private function validateCursorCountValid($cursorCount)
    {
        if ($cursorCount === 0) {
            throw new Exception('Activity state does not exist.', Resource::STATUS_NOT_FOUND);
        }
    }

    private function validateJsonDecodeErrors()
    {
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in existing document. Cannot merge!', Resource::STATUS_BAD_REQUEST);
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
