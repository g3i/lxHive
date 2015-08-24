<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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

class ActivityProfile extends Service
{
    /**
     * Activity profiles.
     *
     * @var array
     */
    protected $activityProfiles;

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
     * Fetches activity profiles according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of activityProfile objects.
     */
    public function activityProfileGet($request)
    {
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('activityProfiles');
        $cursor      = $collection->find();

        // Single activity state
        if ($params->has('profileId')) {
            $cursor->where('profileId', $params->get('profileId'));
            $cursor->where('activityId', $params->get('activityId'));

            if ($cursor->count() === 0) {
                throw new Exception('Activity state does not exist.', Resource::STATUS_NOT_FOUND);
            }

            $this->cursor   = $cursor;
            $this->single = true;

            return $this;
        }

        $cursor->where('activityId', $params->get('activityId'));

        if ($params->has('since')) {
            $since = Util\Date::dateStringToMongoDate($params->get('since'));
            $cursor->whereGreaterOrEqual('mongoTimestamp', $since);
        }

        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Tries to save (merge) an activityProfile.
     */
    public function activityProfilePost($request)
    {
        $params = new Set($request->get());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getBody();

        $collection  = $this->getDocumentManager()->getCollection('activityProfiles');

        // Set up the body to be saved
        $activityProfileDocument = $collection->createDocument();

        // Check for existing state - then merge if applicable
        $cursor      = $collection->find();
        $cursor->where('profileId', $params->get('profileId'));
        $cursor->where('activityId', $params->get('activityId'));

        $result = $cursor->findOne();

        // Check If-Match and If-None-Match here - these SHOULD* exist, but they do not have to
        // See https://github.com/adlnet/xAPI-Spec/blob/1.0.3/xAPI.md#lrs-requirements-7
        // if (!$request->headers('If-Match') && !$request->headers('If-None-Match') && $result) {
        //     throw new \Exception('There was a conflict. Check the current state of the resource and set the "If-Match" header with the current ETag to resolve the conflict.', Resource::STATUS_CONFLICT);
        // }

        // If-Match first
        if ($request->headers('If-Match') && $result && ($this->trimHeader($request->headers('If-Match')) !== $result->getHash())) {
            throw new \Exception('If-Match header doesn\'t match the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
        }

        // Then If-None-Match
        if ($request->headers('If-None-Match')) {
            if ($this->trimHeader($request->headers('If-None-Match')) === '*' && $result) {
                throw new \Exception('If-None-Match header is *, but a resource already exists.', Resource::STATUS_PRECONDITION_FAILED);
            } elseif ($result && $this->trimHeader($request->headers('If-None-Match')) === $result->getHash()) {
                throw new \Exception('If-None-Match header matches the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
            }
        }

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
            $activityProfileDocument = $result;
        }

        $activityProfileDocument->setContent($rawBody);
        // Dates
        $currentDate = new \DateTime();
        $activityProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityProfileDocument->setActivityId($params->get('activityId'));
        $activityProfileDocument->setProfileId($params->get('profileId'));
        $activityProfileDocument->setContentType($contentType);
        $activityProfileDocument->setHash(sha1($rawBody));
        $activityProfileDocument->save();

        $this->single = true;
        $this->activityStates = [$activityProfileDocument];

        return $this;
    }

    /**
     * Tries to PUT (replace) an activityState.
     *
     * @return
     */
    public function activityProfilePut($request)
    {
        // Validation has been completed already - everyhing is assumed to be valid (from an external view!)
        $rawBody = $request->getBody();

        // Single
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('activityProfiles');

        $activityProfileDocument = $collection->createDocument();

        // Check for existing state - then replace if applicable
        $cursor      = $collection->find();
        $cursor->where('profileId', $params->get('profileId'));
        $cursor->where('activityId', $params->get('activityId'));

        $result = $cursor->findOne();

        // Check If-Match and If-None-Match here
        if (!$request->headers('If-Match') && !$request->headers('If-Match') && $result) {
            throw new \Exception('There was a conflict. Check the current state of the resource and set the "If-Match" header with the current ETag to resolve the conflict.', Resource::STATUS_CONFLICT);
        }

        // If-Match first
        if ($request->headers('If-Match') && $result && ($this->trimHeader($request->headers('If-Match')) !== $result->getHash())) {
            throw new \Exception('If-Match header doesn\'t match the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
        }

        // Then If-None-Match
        if ($request->headers('If-None-Match')) {
            if ($this->trimHeader($request->headers('If-None-Match')) === '*' && $result) {
                throw new \Exception('If-None-Match header is *, but a resource already exists.', Resource::STATUS_PRECONDITION_FAILED);
            } elseif ($result && $this->trimHeader($request->headers('If-None-Match')) === $result->getHash()) {
                throw new \Exception('If-None-Match header matches the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
            }
        }

        // ID exists, replace body
        if ($result) {
            $activityProfileDocument = $result;
        }

        $contentType = $request->headers('Content-Type');
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        $activityProfileDocument->setContent($rawBody);
        // Dates
        $currentDate = new \DateTime();
        $activityProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $activityProfileDocument->setActivityId($params->get('activityId'));
        $activityProfileDocument->setProfileId($params->get('profileId'));
        $activityProfileDocument->setContentType($contentType);
        $activityProfileDocument->setHash(sha1($rawBody));
        $activityProfileDocument->save();

        $this->single = true;
        $this->activityProfiles = [$activityProfileDocument];

        return $this;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return self Nothing.
     */
    public function activityProfileDelete($request)
    {
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('activityProfiles');
        $cursor      = $collection->find();

        $cursor->where('profileId', $params->get('profileId'));
        $cursor->where('activityId', $params->get('activityId'));

        $result = $cursor->findOne();

        if (!$result) {
            throw new \Exception('Profile does not exist!.', Resource::STATUS_NOT_FOUND);
        }

        // Check If-Match and If-None-Match here - these SHOULD* exist, but they do not have to
        // See https://github.com/adlnet/xAPI-Spec/blob/1.0.3/xAPI.md#lrs-requirements-7
        // if (!$request->headers('If-Match') && !$request->headers('If-None-Match') && $result) {
        //     throw new \Exception('There was a conflict. Check the current state of the resource and set the "If-Match" header with the current ETag to resolve the conflict.', Resource::STATUS_CONFLICT);
        // }

        // If-Match first
        if ($request->headers('If-Match') && $result && ($this->trimHeader($request->headers('If-Match')) !== $result->getHash())) {
            throw new \Exception('If-Match header doesn\'t match the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
        }

        // Then If-None-Match
        if ($request->headers('If-None-Match')) {
            if ($this->trimHeader($request->headers('If-None-Match')) === '*' && $result) {
                throw new \Exception('If-None-Match header is *, but a resource already exists.', Resource::STATUS_PRECONDITION_FAILED);
            } elseif ($result && $this->trimHeader($request->headers('If-None-Match')) === $result->getHash()) {
                throw new \Exception('If-None-Match header matches the current ETag.', Resource::STATUS_PRECONDITION_FAILED);
            }
        }

        $result->delete();

        return $this;
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

    /**
     * Gets the Activity states.
     *
     * @return array
     */
    public function getActivityProfiles()
    {
        return $this->activityProfiles;
    }

    /**
     * Sets the Activity profiles.
     *
     * @param array $activityProfiles the activity profiles
     *
     * @return self
     */
    public function setActivityProfiles(array $activityProfiles)
    {
        $this->activityProfiles = $activityProfiles;

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
