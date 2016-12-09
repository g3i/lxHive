<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2016 Brightcookie Pty Ltd
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

use API\Resource;
use API\HttpException as Exception;

class ActivityProfile extends Base implements ActivityProfileInterface
{
    public function getActivityProfilesFiltered($parameters)
    {
        $collection = $this->getDocumentManager()->getCollection('activityProfiles');
        $cursor = $collection->find();

        // Single activity state
        if ($parameters->has('profileId')) {
            $cursor->where('profileId', $parameters->get('profileId'));
            $cursor->where('activityId', $parameters->get('activityId'));

            $cursorCount = $cursor->count();
            $this->validateCount($cursorCount);

            $this->cursor = $cursor;
            $this->single = true;

            return $this;
        }

        $cursor->where('activityId', $parameters->get('activityId'));

        if ($parameters->has('since')) {
            $since = Util\Date::dateStringToMongoDate($parameters->get('since'));
            $cursor->whereGreaterOrEqual('mongoTimestamp', $since);
        }

        return $cursor;
    }

    public function postActivityProfile($parameters, $profileObject)
    {
        $collection = $this->getDocumentManager()->getCollection('activityProfiles');

        // Set up the body to be saved
        $activityProfileDocument = $collection->createDocument();

        // Check for existing state - then merge if applicable
        $cursor = $collection->find();
        $cursor->where('profileId', $parameters->get('profileId'));
        $cursor->where('activityId', $parameters->get('activityId'));

        $result = $cursor->findOne();

        $ifMatchHeader = $parameters->get('headers')['If-Match'];
        $ifNoneMatchHeader = $parameters->get('headers')['If-None-Match'];
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        $contentType = $parameters->get('headers')['Content-Type'];
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        // ID exists, try to merge body if applicable
        if ($result) {
            $this->checkDocumentType($result);

            $decodedExisting = json_decode($result->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in existing document. Cannot merge!', Resource::STATUS_BAD_REQUEST);
            }

            $decodedPosted = json_decode($profileObject, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON posted. Cannot merge!', Resource::STATUS_BAD_REQUEST);
            }

            $profileObject = json_encode(array_merge($decodedExisting, $decodedPosted));
            $activityProfileDocument = $result;
        }

        $activityProfileDocument->setContent($profileObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityProfileDocument->setActivityId($parameters->get('activityId'));
        $activityProfileDocument->setProfileId($parameters->get('profileId'));
        $activityProfileDocument->setContentType($contentType);
        $activityProfileDocument->setHash(sha1($profileObject));
        $activityProfileDocument->save();

        // Add to log
        $this->getSlim()->requestLog->addRelation('activityProfiles', $activityProfileDocument)->save();

        return $activityProfileDocument;
    }

    public function putActivityProfile($parameters, $profileObject)
    {
        $collection = $this->getDocumentManager()->getCollection('activityProfiles');

        $activityProfileDocument = $collection->createDocument();

        // Check for existing state - then replace if applicable
        $cursor = $collection->find();
        $cursor->where('profileId', $parameters->get('profileId'));
        $cursor->where('activityId', $parameters->get('activityId'));

        $result = $cursor->findOne();

        $ifMatchHeader = $parameters->get('headers')['If-Match'];
        $ifNoneMatchHeader = $parameters->get('headers')['If-None-Match'];

        $this->validateMatchHeaderExists($ifMatchHeader, $ifNoneMatchHeader, $result);
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        // ID exists, replace body
        if ($result) {
            $activityProfileDocument = $result;
        }

        $contentType = $parameters->get('headers')['Content-Type'];
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        $activityProfileDocument->setContent($profileObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityProfileDocument->setActivityId($parameters->get('activityId'));
        $activityProfileDocument->setProfileId($parameters->get('profileId'));
        $activityProfileDocument->setContentType($contentType);
        $activityProfileDocument->setHash(sha1($profileObject));
        $activityProfileDocument->save();

        // Add to log
        $this->getSlim()->requestLog->addRelation('activityProfiles', $activityProfileDocument)->save();

        return $activityProfileDocument;
    }

    public function deleteActivityProfile($parameters)
    {
        $collection = $this->getDocumentManager()->getCollection('activityProfiles');
        $cursor = $collection->find();

        $cursor->where('profileId', $parameters->get('profileId'));
        $cursor->where('activityId', $parameters->get('activityId'));

        $result = $cursor->findOne();

        $cursorCount = $cursor->count();
        
        if (!$result) {
            throw new Exception('Profile does not exist!.', Resource::STATUS_NOT_FOUND);
        }

        $ifMatchHeader = $parameters->get('headers')['If-Match'];
        $ifNoneMatchHeader = $parameters->get('headers')['If-None-Match'];

        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        // Add to log
        $this->getSlim()->requestLog->addRelation('activityProfiles', $result)->save();

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

    private function checkDocumentType($document)
    {
        if ($document->getContentType() !== 'application/json') {
            throw new Exception('Original document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
        if ($document !== 'application/json') {
            throw new Exception('Posted document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
    }

    private function checkCursorCountValid($cursorCount)
    {
        if ($cursorCount === 0) {
            throw new Exception('Activity state does not exist.', Resource::STATUS_NOT_FOUND);
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
