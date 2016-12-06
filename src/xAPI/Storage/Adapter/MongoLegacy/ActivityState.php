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

class ActivityState extends Base implements ActivityStateInterface
{
    public function getActivityStatesFiltered($parameters)
    {
        $collection  = $this->getDocumentManager()->getCollection('activityStates');
        $cursor      = $collection->find();

        // Single activity state
        if ($parameters->has('stateId')) {
            $cursor->where('stateId', $parameters->get('stateId'));
            $cursor->where('activityId', $parameters->get('activityId'));
            $agent = $parameters->get('agent');
            $agent = json_decode($agent, true);
            //Fetch the identifier - otherwise we'd have to order the JSON
            if (isset($agent['mbox'])) {
                $uniqueIdentifier = 'mbox';
            } elseif (isset($agent['mbox_sha1sum'])) {
                $uniqueIdentifier = 'mbox_sha1sum';
            } elseif (isset($agent['openid'])) {
                $uniqueIdentifier = 'openid';
            } elseif (isset($agent['account'])) {
                $uniqueIdentifier = 'account';
            } else {
                throw new Exception('Invalid request!', Resource::STATUS_BAD_REQUEST);
            }
            $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

            if ($parameters->has('registration')) {
                $cursor->where('registration', $parameters->get('registration'));
            }

            if ($cursor->count() === 0) {
                throw new Exception('Activity state does not exist.', Resource::STATUS_NOT_FOUND);
            }
        }

        $cursor->where('activityId', $parameters->get('activityId'));
        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);
        //Fetch the identifier - otherwise we'd have to order the JSON
        if (isset($agent['mbox'])) {
            $uniqueIdentifier = 'mbox';
        } elseif (isset($agent['mbox_sha1sum'])) {
            $uniqueIdentifier = 'mbox_sha1sum';
        } elseif (isset($agent['openid'])) {
            $uniqueIdentifier = 'openid';
        } elseif (isset($agent['account'])) {
            $uniqueIdentifier = 'account';
        } else {
            throw new Exception('Invalid request!', Resource::STATUS_BAD_REQUEST);
        }
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
        $collection  = $this->getDocumentManager()->getCollection('activityStates');

        // Set up the body to be saved
        $activityStateDocument = $collection->createDocument();

        // Check for existing state - then merge if applicable
        $cursor      = $collection->find();
        $cursor->where('stateId', $parameters->get('stateId'));
        $cursor->where('activityId', $parameters->get('activityId'));

        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);
        //Fetch the identifier - otherwise we'd have to order the JSON
        if (isset($agent['mbox'])) {
            $uniqueIdentifier = 'mbox';
        } elseif (isset($agent['mbox_sha1sum'])) {
            $uniqueIdentifier = 'mbox_sha1sum';
        } elseif (isset($agent['openid'])) {
            $uniqueIdentifier = 'openid';
        } elseif (isset($agent['account'])) {
            $uniqueIdentifier = 'account';
        } else {
            throw new Exception('Invalid request!', Resource::STATUS_BAD_REQUEST);
        }
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
            if ($result->getContentType() !== 'application/json') {
                throw new \Exception('Original document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
            }
            if ($contentType !== 'application/json') {
                throw new \Exception('Posted document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
            }
            $decodedExisting = json_decode($result->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON in existing document. Cannot merge!', Resource::STATUS_BAD_REQUEST);
            }

            $decodedPosted = json_decode($stateObject, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON posted. Cannot merge!', Resource::STATUS_BAD_REQUEST);
            }

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
        $this->getSlim()->requestLog->addRelation('activityStates', $activityStateDocument)->save();

        return $activityStateDocument;

    }

    public function putActivityState($parameters, $stateObject)
    {
        $collection  = $this->getDocumentManager()->getCollection('activityStates');

        $activityStateDocument = $collection->createDocument();

        // Check for existing state - then replace if applicable
        $cursor      = $collection->find();
        $cursor->where('stateId', $parameters->get('stateId'));
        $cursor->where('activityId', $parameters->get('activityId'));

        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);
        //Fetch the identifier - otherwise we'd have to order the JSON
        if (isset($agent['mbox'])) {
            $uniqueIdentifier = 'mbox';
        } elseif (isset($agent['mbox_sha1sum'])) {
            $uniqueIdentifier = 'mbox_sha1sum';
        } elseif (isset($agent['openid'])) {
            $uniqueIdentifier = 'openid';
        } elseif (isset($agent['account'])) {
            $uniqueIdentifier = 'account';
        } else {
            throw new Exception('Invalid request!', Resource::STATUS_BAD_REQUEST);
        }
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
        $this->getSlim()->requestLog->addRelation('activityStates', $activityStateDocument)->save();

        return $activityStateDocument;
    }

    public function deleteActivityState($parameters)
    {
        $collection  = $this->getDocumentManager()->getCollection('activityStates');

        $expression = $collection->expression();

        if ($parameters->has('stateId')) {
            $expression->where('stateId', $parameters->get('stateId'));
        }

        $expression->where('activityId', $parameters->get('activityId'));

        $agent = $parameters->get('agent');
        $agent = json_decode($agent, true);
        //Fetch the identifier - otherwise we'd have to order the JSON
        if (isset($agent['mbox'])) {
            $uniqueIdentifier = 'mbox';
        } elseif (isset($agent['mbox_sha1sum'])) {
            $uniqueIdentifier = 'mbox_sha1sum';
        } elseif (isset($agent['openid'])) {
            $uniqueIdentifier = 'openid';
        } elseif (isset($agent['account'])) {
            $uniqueIdentifier = 'account';
        } else {
            throw new Exception('Invalid request!', Resource::STATUS_BAD_REQUEST);
        }
        $expression->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        if ($parameters->has('registration')) {
            $expression->where('registration', $parameters->get('registration'));
        }

        $collection->deleteDocuments($expression);
    }

}