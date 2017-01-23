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
use API\Resource;
use API\HttpException as Exception;
use API\Storage\Adapter\Base;

class BasicAuth extends Base implements BasicAuthInterface
{
    public function storeToken($name, $description, $expiresAt, $user, $scopes, $key = null, $secret = null)
    {
        $storage = $this->getContainer()['storage'];
        $collection = 'basicTokens';

        $accessTokenDocument = new \API\Document\AccessToken();

        $accessTokenDocument->setName($name);
        $accessTokenDocument->setDescription($description);
        //$accessTokenDocument->addRelation('user', $user);
        /*$scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope->getId();
        }
        $accessTokenDocument->setScopeIds($scopeIds);*/

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

        $storage->insertOne($collection, $accessTokenDocument);

        return $accessTokenDocument;
    }

    public function getToken($key, $secret)
    {
        $collection = 'basicTokens';
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('key', $key);
        $expression->where('secret', $secret);
        $accessTokenDocument = $storage->findOne($collection, $expression);

        $this->validateAccessTokenNotEmpty($accessTokenDocument);

        $expiresAt = $accessTokenDocument['expiresAt'];

        $this->validateExpiresAt($expiresAt);

        return $accessTokenDocument;
    }

    public function deleteToken($clientId)
    {
        $collection = 'basicTokens';
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('clientId', $clientId);

        $deletionResult = $storage->delete($expression);
        return $deletionResult;
    }

    public function expireToken($clientId, $accessToken)
    {
        $collection = 'basicTokens';
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('token', $accessToken);
        $expression->where('clientId', $clientId);
        $updateResult = $storage->update($collection, $expression, ['expired' => true]);

        return $updateResult;
    }

    public function getTokens()
    {
        $collection = 'basicTokens';
        $storage = $this->getContainer()['storage'];
        $cursor = $storage->find($collection);

        return $cursor;
    }

    public function getScopeByName($name)
    {
        $storage = $this->getContainer()['storage'];
        $collection = 'authScopes';
        $expression = $storage->createExpression();
        $expression->where('name', $name);
        $scopeDocument = $storage->findOne($collection, $expression);

        $this->validateScope($scopeDocument);

        return $scopeDocument;
    }

    private function validateScope($scope)
    {
        if (null === $scope) {
            throw new Exception('Invalid scope given!', Resource::STATUS_BAD_REQUEST);
        }
    }

    private function validateExpiresAt($expiresAt)
    {
        if ($expiresAt !== null) {
            if ($expiresAt->sec <= time()) {
                throw new \Exception('Expired token.', Resource::STATUS_FORBIDDEN);
            }
        }
    }

    private function validateAccessTokenNotEmpty($accessToken)
    {
        if ($accessToken === null) {
            throw new \Exception('Invalid credentials.', Resource::STATUS_FORBIDDEN);
        }
    }
}
