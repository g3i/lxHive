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

class AgentProfile extends Service
{
    /**
     * Activity profiles.
     *
     * @var array
     */
    protected $agentProfiles;

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
     * Fetches agent profiles according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of agentProfile objects.
     */
    public function agentProfileGet($request)
    {
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('agentProfiles');
        $cursor      = $collection->find();

        // Single activity profile
        if ($params->has('profileId')) {
            $cursor->where('profileId', $params->get('profileId'));
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
            }
            $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

            if ($cursor->count() === 0) {
                throw new Exception('Agent profile does not exist.', Resource::STATUS_NOT_FOUND);
            }

            $this->cursor   = $cursor;
            $this->single = true;

            return $this;
        }

        $agent = $params->get('agent');
        $agent = json_decode($agent);
        $cursor->where('agent', $agent);

        if ($params->has('since')) {
            $since = Util\Date::dateStringToMongoDate($params->get('since'));
            $cursor->whereGreaterOrEqual('mongoTimestamp', $since);
        }

        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Tries to save (merge) an agentProfile.
     */
    public function agentProfilePost($request)
    {
        $params = new Set($request->get());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getBody();

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
        }

        $collection  = $this->getDocumentManager()->getCollection('agentProfiles');

        // Set up the body to be saved
        $agentProfileDocument = $collection->createDocument();

        // Check for existing state - then merge if applicable
        $cursor      = $collection->find();
        $cursor->where('profileId', $params->get('profileId'));
        $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        $result = $cursor->findOne();

        // Check If-Match and If-None-Match here
        if (!$request->headers('If-Match') && !$request->headers('If-None-Match') && $result) {
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
            $agentProfileDocument = $result;
        }

        $agentProfileDocument->setContent($rawBody);
        // Dates
        $currentDate = new \DateTime();
        $agentProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $agentProfileDocument->setAgent($agent);
        $agentProfileDocument->setProfileId($params->get('profileId'));
        $agentProfileDocument->setContentType($contentType);
        $agentProfileDocument->setHash(sha1($rawBody));
        $agentProfileDocument->save();

        $this->single = true;
        $this->activityStates = [$agentProfileDocument];

        return $this;
    }

    /**
     * Tries to PUT (replace) an agentProfile.
     *
     * @return
     */
    public function agentProfilePut($request)
    {
        // Validation has been completed already - everyhing is assumed to be valid (from an external view!)
        $rawBody = $request->getBody();
        $body = json_decode($rawBody, true);

        // Some clients escape the JSON - handle them
        if (is_string($body)) {
            $body = json_decode($body, true);
        }

        // Single
        $params = new Set($request->get());

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
        }

        $collection  = $this->getDocumentManager()->getCollection('agentProfiles');

        $agentProfileDocument = $collection->createDocument();

        // Check for existing state - then replace if applicable
        $cursor      = $collection->find();
        $cursor->where('profileId', $params->get('profileId'));
        $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

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
            $agentProfileDocument = $result;
        }

        $contentType = $request->headers('Content-Type');
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        $agentProfileDocument->setContent($rawBody);
        // Dates
        $currentDate = new \DateTime();
        $agentProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));

        $agentProfileDocument->setAgent($agent);
        $agentProfileDocument->setProfileId($params->get('profileId'));
        $agentProfileDocument->setContentType($contentType);
        $agentProfileDocument->setHash(sha1($rawBody));
        $agentProfileDocument->save();

        $this->single = true;
        $this->activityProfiles = [$agentProfileDocument];

        return $this;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return self Nothing.
     */
    public function agentProfileDelete($request)
    {
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('agentProfiles');
        $cursor      = $collection->find();

        $cursor->where('profileId', $params->get('profileId'));
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
        }
        $cursor->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        $result = $cursor->findOne();

        if (!$result) {
            throw new \Exception('Profile does not exist!.', Resource::STATUS_NOT_FOUND);
        }

        // Check If-Match and If-None-Match here
        if (!$request->headers('If-Match') && !$request->headers('If-None-Match') && $result) {
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
     * Gets the Agent profiles.
     *
     * @return array
     */
    public function getAgentProfiles()
    {
        return $this->agentProfiles;
    }

    /**
     * Sets the Agent profiles.
     *
     * @param array $agentProfiles the agent profiles
     *
     * @return self
     */
    public function setAgentProfiles(array $agentProfiles)
    {
        $this->agentProfiles = $agentProfiles;

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
     * Gets the Is this a single agent profile fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single agent profile fetch?.
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
