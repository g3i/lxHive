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

use API\Storage\Query\OAuthInterface;
use API\Controller;
use API\HttpException as Exception;
use API\Storage\Provider;
use API\Util;

class OAuth extends Provider implements OAuthInterface
{
    const COLLECTION_NAME = 'oAuthTokens';

    public function storeToken($expiresAt, $user, $client, array $scopes = [], $code = null)
    {
        $storage = $this->getContainer()['storage'];

        $accessTokenDocument = new \API\Document\Generic();

        $expiresDate = new \DateTime();
        $expiresDate->setTimestamp($expiresAt);
        $accessTokenDocument->setExpiresAt(Util\Date::dateTimeToMongoDate($expiresDate));
        $currentDate = new \DateTime();
        $accessTokenDocument->setCreatedAt(Util\Date::dateTimeToMongoDate($currentDate));

        $accessTokenDocument->setUserId($user->getId());
        $accessTokenDocument->setClientId($client->getId());

        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope['id'];
        }
        $accessTokenDocument->setScopeIds($scopeIds);

        $accessTokenDocument->setToken(Util\OAuth::generateToken());
        if (null !== $code) {
            $accessTokenDocument->setCode($code);
        }

        $storage->insertOne(self::COLLECTION_NAME, $accessTokenDocument);

        return $accessTokenDocument;
    }

    public function getToken($accessToken)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('token', $accessToken);
        $accessTokenDocument = $storage->findOne(self::COLLECTION_NAME, $expression);

        $this->validateAccessTokenNotEmpty($accessTokenDocument);

        $accessTokenDocument = new \API\Document\Generic($accessTokenDocument);

        $expiresAt = $accessTokenDocument->getExpiresAt();

        $this->validateExpiresAt($expiresAt);

        return $accessTokenDocument;
    }

    public function deleteToken($accessToken)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('token', $accessToken);

        $storage->delete(self::COLLECTION_NAME, $expression);
    }

    public function expireToken($accessToken)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('token', $accessToken);
        $storage->update(self::COLLECTION_NAME, $expression, ['expired' => true]);
    }

    public function addClient($name, $description, $redirectUri)
    {
        $storage = $this->getContainer()['storage'];

        // Set up the Client to be saved
        $clientDocument = new \API\Document\Generic();

        $clientDocument->setName($name);

        $clientDocument->setDescription($description);

        $clientDocument->setRedirectUri($redirectUri);

        $clientId = Util\OAuth::generateToken();
        $clientDocument->setClientId($clientId);

        $secret = Util\OAuth::generateToken();
        $clientDocument->setSecret($secret);

        $storage->insertOne(self::COLLECTION_NAME, $clientDocument);

        return $clientDocument;
    }

    public function getClients()
    {
        $storage = $this->getContainer()['storage'];

        $cursor = $storage->find(self::COLLECTION_NAME);
        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }

    public function getClientById($id)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('clientId', $id);
        $clientDocument = $storage->findOne(self::COLLECTION_NAME, $expression);

        return $clientDocument;
    }

    public function addScope($name, $description)
    {
        $storage = $this->getContainer()['storage'];

        // Set up the Client to be saved
        $scopeDocument = new \API\Document\Generic();

        $scopeDocument->setName($name);

        $scopeDocument->setDescription($description);

        $storage->insertOne(AuthScopes::COLLECTION_NAME, $scopeDocument);

        return $scopeDocument;
    }

    public function getScopeByName($name)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('name', $name);
        $scopeDocument = $storage->findOne(AuthScopes::COLLECTION_NAME, $expression);

        return $scopeDocument;
    }

    public function getTokenWithOneTimeCode($params)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('code', $params['code']);

        $tokenDocument = $storage->findOne(self::COLLECTION_NAME, $expression);

        $this->validateAccessTokenNotEmpty($tokenDocument);
        $tokenDocument = new \API\Document\AccessToken($tokenDocument);

        // TODO: This will be removed soon
        $clientDocument = $tokenDocument->client;

        $this->validateClientSecret($params, $clientDocument);

        $this->validateRedirectUri($params, $clientDocument);

        //Remove one-time code

        $tokenDocument->setCode(false);

        $storage->update(self::COLLECTION_NAME, $expression, $tokenDocument);

        return $tokenDocument;
    }

    private function validateExpiresAt($expiresAt)
    {
        if ($expiresAt !== null) {
            if ($expiresAt->sec <= time()) {
                throw new Exception('Expired token.', Controller::STATUS_FORBIDDEN);
            }
        }
    }

    private function validateAccessTokenNotEmpty($accessToken)
    {
        if ($accessToken === null) {
            throw new Exception('Invalid credentials.', Controller::STATUS_FORBIDDEN);
        }
    }

    private function validateClientSecret($params, $clientDocument)
    {
        if ($clientDocument->getClientId() !== $params['client_id'] || $clientDocument->getSecret() !== $params['client_secret']) {
            throw new Exception('Invalid client_id/client_secret combination!', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateRedirectUri($params, $clientDocument)
    {
        if ($params['redirect_uri'] !== $clientDocument->getRedirectUri()) {
            throw new Exception('Redirect_uri mismatch!', Controller::STATUS_BAD_REQUEST);
        }
    }
}
