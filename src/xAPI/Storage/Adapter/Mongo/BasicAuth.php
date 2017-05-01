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

use API\Storage\Query\BasicAuthInterface;
use API\Controller;
use API\HttpException as Exception;
use API\Storage\Provider;

class BasicAuth extends Provider implements BasicAuthInterface
{
    const COLLECTION_NAME = 'basicTokens';

    public function storeToken($name, $description, $expiresAt, $user, $scopes, $key = null, $secret = null)
    {
        $storage = $this->getContainer()['storage'];

        $accessTokenDocument = new \API\Document\AccessToken();

        $accessTokenDocument->setName($name);
        $accessTokenDocument->setDescription($description);
        //$accessTokenDocument->addRelation('user', $user);
        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope['id'];
        }
        $accessTokenDocument->setScopeIds($scopeIds);

        if (isset($expiresAt)) {
            $expiresDate = new \DateTime();
            $expiresDate->setTimestamp($expiresAt);
            $accessTokenDocument->setExpiresAt(\API\Util\Date::dateTimeToMongoDate($expiresDate));
        }

        if (null !== $key) {
            $accessTokenDocument->setKey($key);
        } else {
            // Generate token
            $accessTokenDocument->setKey(\API\Util\OAuth::generateToken());
        }

        if (null !== $secret) {
            $accessTokenDocument->setSecret($secret);
        } else {
            // Generate token
            $accessTokenDocument->setSecret(\API\Util\OAuth::generateToken());
        }

        $currentDate = new \DateTime();
        $accessTokenDocument->setCreatedAt(\API\Util\Date::dateTimeToMongoDate($currentDate));

        $storage->insertOne(self::COLLECTION_NAME, $accessTokenDocument);

        return $accessTokenDocument;
    }

    public function getToken($key, $secret)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('key', $key);
        $expression->where('secret', $secret);
        $accessTokenDocument = $storage->findOne(self::COLLECTION_NAME, $expression);

        $this->validateAccessTokenNotEmpty($accessTokenDocument);

        $expiresAt = $accessTokenDocument['expiresAt'];

        $this->validateExpiresAt($expiresAt);

        return $accessTokenDocument;
    }

    public function deleteToken($clientId)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('clientId', $clientId);

        $deletionResult = $storage->delete($expression);
        return $deletionResult;
    }

    public function expireToken($clientId, $accessToken)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('token', $accessToken);
        $expression->where('clientId', $clientId);
        $updateResult = $storage->update(self::COLLECTION_NAME, $expression, ['expired' => true]);

        return $updateResult;
    }

    public function getTokens()
    {
        $storage = $this->getContainer()['storage'];
        $cursor = $storage->find(self::COLLECTION_NAME);

        return $cursor;
    }

    public function getScopeByName($name)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();
        $expression->where('name', $name);
        $scopeDocument = $storage->findOne(self::COLLECTION_NAME, $expression);

        $this->validateScope($scopeDocument);

        return $scopeDocument;
    }

    private function validateScope($scope)
    {
        if (null === $scope) {
            throw new Exception('Invalid scope given!', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateExpiresAt($expiresAt)
    {
        if ($expiresAt !== null) {
            if ($expiresAt->sec <= time()) {
                throw new \Exception('Expired token.', Controller::STATUS_FORBIDDEN);
            }
        }
    }

    private function validateAccessTokenNotEmpty($accessToken)
    {
        if ($accessToken === null) {
            throw new \Exception('Invalid credentials.', Controller::STATUS_FORBIDDEN);
        }
    }
}
