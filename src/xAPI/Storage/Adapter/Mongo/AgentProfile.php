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

use API\Storage\Query\AgentProfileInterface;
use API\Storage\Query\DocumentResult;
use API\Util;
use API\Resource;
use API\HttpException as Exception;
use API\Storage\Adapter\Base;

class AgentProfile extends Base implements AgentProfileInterface
{
    const COLLECTION_NAME = 'agentProfiles';

    public function getAgentProfilesFiltered($parameters)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        // Single activity profile
        if ($parameters->has('profileId')) {
            $expression->where('profileId', $parameters['profileId']);
            $agent = $parameters['agent'];
            $agent = json_decode($agent, true);

            $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

            $expression->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

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

        $agent = $parameters['agent'];
        $agent = json_decode($agent);
        $expression->where('agent', $agent);

        if ($parameters->has('since')) {
            $since = Util\Date::dateStringToMongoDate($parameters['since']);
            $expression->whereGreaterOrEqual('mongoTimestamp', $since);
        }

        // Fetch
        $cursor = $storage->find(self::COLLECTION_NAME, $expression);

        $documentResult = new DocumentResult();
        $documentResult->setCursor($cursor);
        $documentResult->setIsSingle(false);

        return $documentResult;
    }

    public function postAgentProfile($parameters, $profileObject)
    {
        $profileObject = (string)$profileObject;
        $agent = $parameters['agent'];
        $agent = json_decode($agent, true);

        $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

        $storage = $this->getContainer()['storage'];

        // Set up the body to be saved
        $agentProfileDocument = new \API\Document\Generic();

        // Check for existing state - then merge if applicable
        $expression = $storage->createExpression();
        $expression->where('profileId', $parameters['profileId']);
        $expression->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        $result = $storage->findOne(self::COLLECTION_NAME, $expression);
        if ($result) {
            $result = new \API\Document\Generic($result);
        }
    
        $ifMatchHeader = isset($parameters['headers']['if-match']) ? $parameters['headers']['if-none-match'] : null;
        $ifNoneMatchHeader = isset($parameters['headers']['if-none-match']) ? $parameters['headers']['if-none-match'] : null;
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        // ID exists, merge body
        $contentType = $parameters['headers']['content-type'];
        if ($contentType === null) {
            $contentType = 'text/plain';
        } else {
            $contentType = $contentType[0];
        }

        // ID exists, try to merge body if applicable
        if ($result) {
            $this->validateDocumentType($result);

            $decodedExisting = json_decode($result->getContent(), true);
            $this->validateJsonDecodeErrors();

            $decodedPosted = json_decode($profileObject, true);
            $this->validateJsonDecodeErrors();

            $profileObject = json_encode(array_merge($decodedExisting, $decodedPosted));
            $agentProfileDocument = $result;
        }

        $agentProfileDocument->setContent($profileObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $agentProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $agentProfileDocument->setAgent($agent);
        $agentProfileDocument->setProfileId($parameters['profileId']);
        $agentProfileDocument->setContentType($contentType);
        $agentProfileDocument->setHash(sha1($profileObject));
        
        $storage->upsert(self::COLLECTION_NAME, $expression, $agentProfileDocument);

        // Add to log
        //$this->getContainer()->requestLog->addRelation('agentProfiles', $agentProfileDocument)->save();

        return $agentProfileDocument;
    }

    public function putAgentProfile($parameters, $profileObject)
    {
        $agent = $parameters['agent'];
        $agent = json_decode($agent, true);

        $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

        $storage = $this->getContainer()['storage'];

        // Set up the body to be saved
        $agentProfileDocument = new \API\Document\Generic();

        // Check for existing state - then replace if applicable
        $expression = $storage->createExpression();
        $expression->where('profileId', $parameters['profileId']);
        $expression->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        $result = $storage->findOne(self::COLLECTION_NAME, $expression);
        if ($result) {
            $result = new \API\Document\Generic($result);
        }
        
        $ifMatchHeader = $parameters['headers']['If-Match'];
        $ifNoneMatchHeader = $parameters['headers']['If-None-Match'];
        $this->validateMatchHeaderExists($ifMatchHeader, $ifNoneMatchHeader, $result);
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        // ID exists, replace body
        if ($result) {
            $agentProfileDocument = $result;
        }

        $contentType = $parameters['headers']['Content-Type'];
        if ($contentType === null) {
            $contentType = 'text/plain';
        }

        $agentProfileDocument->setContent($profileObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $agentProfileDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));

        $agentProfileDocument->setAgent($agent);
        $agentProfileDocument->setProfileId($parameters['profileId']);
        $agentProfileDocument->setContentType($contentType);
        $agentProfileDocument->setHash(sha1($profileObject));
        
        $storage->upsert(self::COLLECTION_NAME, $expression, $agentProfileDocument);

        // Add to log
        //$this->getContainer()->requestLog->addRelation('agentProfiles', $agentProfileDocument)->save();

        return $agentProfileDocument;
    }

    public function deleteAgentProfile($parameters)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('profileId', $parameters['profileId']);
        $agent = $parameters['agent'];
        $agent = json_decode($agent, true);

        $uniqueIdentifier = Util\xAPI::extractUniqueIdentifier($agent);

        $expression->where('agent.'.$uniqueIdentifier, $agent[$uniqueIdentifier]);

        $result = $storage->findOne(self::COLLECTION_NAME, $expression);

        if (!$result) {
            throw new \Exception('Profile does not exist!.', Resource::STATUS_NOT_FOUND);
        }

        $ifMatchHeader = $parameters['headers']['If-Match'];
        $ifNoneMatchHeader = $parameters['headers']['If-None-Match'];
        $this->validateMatchHeaders($ifMatchHeader, $ifNoneMatchHeader, $result);

        // Add to log
        //$this->getContainer()->requestLog->addRelation('agentProfiles', $result)->save();

        $deletionResult = $storage->delete(self::COLLECTION_NAME, $expression);
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

    private function validateDocumentType($document)
    {
        if ($document->getContentType() !== 'application/json') {
            throw new Exception('Original document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
        if ($document !== 'application/json') {
            throw new Exception('Posted document is not JSON. Cannot merge!', Resource::STATUS_BAD_REQUEST);
        }
    }

    private function validateCursorCountValid($cursorCount)
    {
        if ($cursorCount === 0) {
            throw new Exception('Agent profile does not exist.', Resource::STATUS_NOT_FOUND);
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
