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

use API\Storage\Query\ActivityStateInterface;
use API\Util;
use API\Resource;
use API\HttpException as Exception;

class ActivityState extends Base implements ActivityStateInterface
{
    public function getActivityStatesFiltered(\Traversable $parameters)
    {
        $collection = $this->getDocumentManager()->getCollection('activityStates');
        $cursor = $collection->find();

        // Single activity state
        if (isset($parameters['stateId'])) {
            $cursor->where('stateId', $parameters->get('stateId'));
            $cursor->where('activityId', $parameters->get('activityId'));
            $agent = $parameters->get('agent');
            $agent = json_decode($agent, true);

            $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

            $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

            if (isset($parameters['registration'])) {
                $cursor->where('registration', $parameters->get('registration'));
            }

            $cursorCount = $cursor->count();

            $this->validateCursorCountValid($cursorCount);
        }

        $cursor->where('activityId', $parameters->get('activityId'));
        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);

        $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

        $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        if ($parameters->has('registration')) {
            $cursor->where('registration', $parameters->get('registration'));
        }

        if ($parameters->has('since')) {
            $since = Util\Date::dateStringToMongoDate($parameters->get('since'));
            $cursor->whereGreaterOrEqual('mongoTimestamp', $since);
        }

        return $cursor;
    }

    public function postActivityState($parameters, $stateObject)
    {
        $collection = $this->getDocumentManager()->getCollection('activityStates');

        // Set up the body to be saved
        $activityStateDocument = $collection->createDocument();

        // Check for existing state - then merge if applicable
        $cursor = $collection->find();
        $cursor->where('stateId', $parameters->get('stateId'));
        $cursor->where('activityId', $parameters->get('activityId'));

        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);

        $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

        $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        if ($parameters->has('registration')) {
            $cursor->where('registration', $parameters->get('registration'));
        }

        $result = $cursor->findOne();

        // ID exists, merge body
        $contentType = $request->headers('Content-Type');
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        // ID exists, try to merge body if applicable
        if ($result) {
            $this->validateDocumentType($result, $contentType);

            $decodedExisting = json_decode($result->getContent(), true);
            $this->validateJsonDecodeErrors();

            $decodedPosted = json_decode($stateObject, true);
            $this->validateJsonDecodeErrors();

            $stateObject = json_encode(array_merge($decodedExisting, $decodedPosted));
            $activityStateDocument = $result;
        }

        $activityStateDocument->setContent($stateObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityStateDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));

        $activityStateDocument->setActivityId($parameters->get('activityId'));
        $activityStateDocument->setAgent($agent);
        if ($parameters->has('registration')) {
            $activityStateDocument->setRegistration($parameters->get('registration'));
        }
        $activityStateDocument->setStateId($parameters->get('stateId'));
        $activityStateDocument->setContentType($contentType);
        $activityStateDocument->setHash(sha1($stateObject));
        $activityStateDocument->save();

        // TODO: Abstract this away somehow!
        // Add to log
        $this->getContainer()->requestLog->addRelation('activityStates', $activityStateDocument)->save();

        return $activityStateDocument;
    }

    public function putActivityState($parameters, $stateObject)
    {
        $collection = $this->getDocumentManager()->getCollection('activityStates');

        $activityStateDocument = $collection->createDocument();

        // Check for existing state - then replace if applicable
        $cursor = $collection->find();
        $cursor->where('stateId', $parameters->get('stateId'));
        $cursor->where('activityId', $parameters->get('activityId'));

        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);

        $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

        $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        if ($parameters->has('registration')) {
            $cursor->where('registration', $parameters->get('registration'));
        }

        $result = $cursor->findOne();

        $contentType = $request->headers('Content-Type');
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        // ID exists, replace
        if ($result) {
            $activityStateDocument = $result;
        }

        $activityStateDocument->setContent($stateObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityStateDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityStateDocument->setActivityId($parameters->get('activityId'));

        $activityStateDocument->setAgent($agent);
        if ($parameters->has('registration')) {
            $activityStateDocument->setRegistration($parameters->get('registration'));
        }
        $activityStateDocument->setStateId($parameters->get('stateId'));
        $activityStateDocument->setContentType($contentType);
        $activityStateDocument->setHash(sha1($stateObject));
        $activityStateDocument->save();

        // TODO: Abstract this away somehow!
        // Add to log
        $this->getContainer()->requestLog->addRelation('activityStates', $activityStateDocument)->save();

        return $activityStateDocument;
    }

    public function deleteActivityState($parameters)
    {
        $collection = $this->getDocumentManager()->getCollection('activityStates');

        $expression = $collection->expression();

        if ($parameters->has('stateId')) {
            $expression->where('stateId', $parameters->get('stateId'));
        }

        $expression->where('activityId', $parameters->get('activityId'));

        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);

        $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

        $expression->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        if ($parameters->has('registration')) {
            $expression->where('registration', $parameters->get('registration'));
        }

        $collection->deleteDocuments($expression);
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

    private function validateDocumentType($document, $contentType)
    {
        if ($document->getContentType() !== 'application/json') {
            throw new Exception('Original document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
        if ($contentType !== 'application/json') {
            throw new Exception('Posted document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
    }
}
