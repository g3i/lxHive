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

namespace API\Service;

use API\Service;
use API\Resource;
use API\Util;
use Slim\Helper\Set;
use Sokil\Mongo\Cursor;

class ActivityState extends Service
{
    /**
     * Activity states.
     *
     * @var array
     */
    protected $activityStates;

    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

    /**
     * Is this a single activity state fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of statement objects.
     */
    public function activityStateGet($request)
    {
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('activityStates');
        $cursor      = $collection->find();

        // Single activity state
        if ($params->has('stateId')) {
            $cursor->where('stateId', $params->get('stateId'));
            $cursor->where('activityId', $params->get('activityId'));
            $agent = $params->get('agent');
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

            if ($params->has('registration')) {
                $cursor->where('registration', $params->get('registration'));
            }

            if ($cursor->count() === 0) {
                throw new Exception('Activity state does not exist.', Resource::STATUS_NOT_FOUND);
            }

            $this->cursor   = $cursor;
            $this->single = true;

            return $this;
        }

        $cursor->where('activityId', $params->get('activityId'));
        $agent = $params->get('agent');
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

        if ($params->has('registration')) {
            $cursor->where('registration', $params->get('registration'));
        }

        if ($params->has('since')) {
            $since = Util\Date::dateStringToMongoDate($params->get('since'));
            $cursor->whereGreaterOrEqual('mongoTimestamp', $since);
        }

        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Tries to save (merge) an activityState.
     */
    public function activityStatePost($request)
    {
        $params = new Set($request->get());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getBody();

        $collection  = $this->getDocumentManager()->getCollection('activityStates');

        // Set up the body to be saved
        $activityStateDocument = $collection->createDocument();

        // Check for existing state - then merge if applicable
        $cursor      = $collection->find();
        $cursor->where('stateId', $params->get('stateId'));
        $cursor->where('activityId', $params->get('activityId'));

        $agent = $params->get('agent');
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

        if ($params->has('registration')) {
            $cursor->where('registration', $params->get('registration'));
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

            $decodedPosted = json_decode($rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON posted. Cannot merge!', Resource::STATUS_BAD_REQUEST);
            }

            $rawBody = json_encode(array_merge($decodedExisting, $decodedPosted));
            $activityStateDocument = $result;
        }

        $activityStateDocument->setContent($rawBody);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityStateDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));

        $activityStateDocument->setActivityId($params->get('activityId'));
        $activityStateDocument->setAgent($agent);
        if ($params->has('registration')) {
            $activityStateDocument->setRegistration($params->get('registration'));
        }
        $activityStateDocument->setStateId($params->get('stateId'));
        $activityStateDocument->setContentType($contentType);
        $activityStateDocument->setHash(sha1($rawBody));
        $activityStateDocument->save();

        // Add to log
        $this->getSlim()->requestLog->addRelation('activityStates', $activityStateDocument)->save();

        $this->single = true;
        $this->activityStates = [$activityStateDocument];

        return $this;
    }

    /**
     * Tries to PUT (replace) an activityState.
     *
     * @return
     */
    public function activityStatePut($request)
    {
        // Validation has been completed already - everyhing is assumed to be valid (from an external view!)
        $rawBody = $request->getBody();

        // Single
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('activityStates');

        $activityStateDocument = $collection->createDocument();

        // Check for existing state - then replace if applicable
        $cursor      = $collection->find();
        $cursor->where('stateId', $params->get('stateId'));
        $cursor->where('activityId', $params->get('activityId'));

        $agent = $params->get('agent');
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

        if ($params->has('registration')) {
            $cursor->where('registration', $params->get('registration'));
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

        $activityStateDocument->setContent($rawBody);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $activityStateDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityStateDocument->setActivityId($params->get('activityId'));

        $activityStateDocument->setAgent($agent);
        if ($params->has('registration')) {
            $activityStateDocument->setRegistration($params->get('registration'));
        }
        $activityStateDocument->setStateId($params->get('stateId'));
        $activityStateDocument->setContentType($contentType);
        $activityStateDocument->setHash(sha1($rawBody));
        $activityStateDocument->save();

        // Add to log
        $this->getSlim()->requestLog->addRelation('activityStates', $activityStateDocument)->save();

        $this->single = true;
        $this->activityStates = [$activityStateDocument];

        return $this;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of statement objects.
     */
    public function activityStateDelete($request)
    {
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('activityStates');

        $expression = $collection->expression();

        if ($params->has('stateId')) {
            $expression->where('stateId', $params->get('stateId'));
        }

        $expression->where('activityId', $params->get('activityId'));

        $agent = $params->get('agent');
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

        if ($params->has('registration')) {
            $expression->where('registration', $params->get('registration'));
        }

        $collection->deleteDocuments($expression);

        return $this;
    }

    /**
     * Gets the Activity states.
     *
     * @return array
     */
    public function getActivityStates()
    {
        return $this->activityStates;
    }

    /**
     * Sets the Activity states.
     *
     * @param array $activityStates the activity states
     *
     * @return self
     */
    public function setActivityStates(array $activityStates)
    {
        $this->activityStates = $activityStates;

        return $this;
    }

    /**
     * Gets the Cursor.
     *
     * @return cursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Sets the Cursor.
     *
     * @param cursor $cursor the cursor
     *
     * @return self
     */
    public function setCursor(Cursor $cursor)
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Gets the Is this a single activity state fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single activity state fetch?.
     *
     * @param bool $single the is single
     *
     * @return self
     */
    public function setSingle($single)
    {
        $this->single = $single;

        return $this;
    }
}
