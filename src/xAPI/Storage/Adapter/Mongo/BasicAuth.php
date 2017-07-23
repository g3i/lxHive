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

use API\Storage\SchemaInterface;
use API\Storage\Query\BasicAuthInterface;

use API\Controller;
use API\Storage\Provider;
use API\HttpException as Exception;

class BasicAuth extends Provider implements BasicAuthInterface, SchemaInterface
{
    const COLLECTION_NAME = 'basicTokens';


    /**
     * @var array $indexes
     *
     * @see https://docs.mongodb.com/manual/reference/command/createIndexes/
     *  [
     *      name: <index_name>,
     *      key: [
     *          <key-value_pair>,
     *          <key-value_pair>,
     *          ...
     *      ],
     *      <option1-value_pair>,
     *      <option1-value_pair>,
     *      ...
     *  ],
     */
    private $indexes = [
        [
            'name' => 'key.unique',
            'key'  => [
                'key' => 1
            ],
            'unique' => true,
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function install()
    {
        $container = $this->getContainer()['storage'];
        $container->executeCommand(['create' => self::COLLECTION_NAME]);
        $container->createIndexes(self::COLLECTION_NAME, $this->indexes);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    public function storeToken($name, $description, $expiresAt, $user, $scopes, $key = null, $secret = null)
    {
        $storage = $this->getContainer()['storage'];
        $accessTokenDocument = new \API\Document\AccessToken();

        $accessTokenDocument->setName($name);
        $accessTokenDocument->setDescription($description);
        $accessTokenDocument->setUserId($user->_id);
        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope->_id;
        }
        $accessTokenDocument->setScopeIds($scopeIds);
        $accessTokenDocument->setExpiresAt($expiresAt);

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

        $expiresAt = $accessTokenDocument->expiresAt;

        $this->validateExpiresAt($expiresAt);

        return $accessTokenDocument;
    }

    public function deleteToken($key)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('key', $key);

        $deletionResult = $storage->delete(self::COLLECTION_NAME, $expression);

        return $deletionResult;
    }

    public function expireToken($key)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('key', $key);

        $updateResult = $storage->update(self::COLLECTION_NAME, $expression, ['$set' => ['expired' => true]]);

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
        $scopeDocument = $storage->findOne(AuthScopes::COLLECTION_NAME, $expression);

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
