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

use API\Storage\Query\OAuthInterface;
use API\Resource;
use API\HttpException as Exception;

class OAuth extends Base implements OAuthInterface
{
    public function storeToken($expiresAt, $user, $client, array $scopes = [], $code = null)
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthTokens');

        $accessTokenDocument = $collection->createDocument();

        $expiresDate = new \DateTime();
        $expiresDate->setTimestamp($expiresAt);
        $accessTokenDocument->setExpiresAt(\API\Util\Date::dateTimeToMongoDate($expiresDate));
        $currentDate = new \DateTime();
        $accessTokenDocument->setCreatedAt(\API\Util\Date::dateTimeToMongoDate($currentDate));
        $accessTokenDocument->addRelation('user', $user);
        $accessTokenDocument->addRelation('client', $client);
        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope->getId();
        }
        $accessTokenDocument->setScopeIds($scopeIds);

        $accessTokenDocument->setToken(Util\OAuth::generateToken());
        if (null !== $code) {
            $accessTokenDocument->setCode($code);
        }

        $accessTokenDocument->save();

        return $accessTokenDocument;
    }

    public function getToken($accessToken)
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthTokens');
        $cursor = $collection->find();

        $cursor->where('token', $accessToken);
        $accessTokenDocument = $cursor->current();

        if ($accessTokenDocument === null) {
            throw new \Exception('Invalid access token specified.', Resource::STATUS_FORBIDDEN);
        }

        $expiresAt = $accessTokenDocument->getExpiresAt();

        if ($expiresAt !== null) {
            if ($expiresAt->sec <= time()) {
                throw new \Exception('Expired token.', Resource::STATUS_FORBIDDEN);
            }
        }

        return $accessTokenDocument;
    }

    public function deleteToken($accessToken)
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthTokens');

        $expression = $collection->expression();
        $expression->where('token', $accessToken);
        $collection->deleteDocuments($expression);
    }

    public function expireToken($accessToken)
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthTokens');
        $cursor = $collection->find();

        $cursor->where('token', $accessToken);
        $accessTokenDocument = $cursor->current();
        $accessTokenDocument->setExpired(true);
        $accessTokenDocument->save();
    }

    public function storeClient($name, $description, $redirectUri)
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthClients');

        // Set up the Client to be saved
        $clientDocument = $collection->createDocument();

        $clientDocument->setName($name);

        $clientDocument->setDescription($description);

        $clientDocument->setRedirectUri($redirectUri);

        $clientId = Util\OAuth::generateToken();
        $clientDocument->setClientId($clientId);

        $secret = Util\OAuth::generateToken();
        $clientDocument->setSecret($secret);

        $clientDocument->save();

        return $clientDocument;
    }

    public function getClients()
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthClients');
        $cursor = $collection->find();

        return $cursor;
    }

    public function getClientById($id)
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthClients');
        $cursor = $collection->find();

        $cursor->where('clientId', $id);
        $clientDocument = $cursor->current();

        return $clientDocument;
    }

    public function addScope($name, $description)
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');

        // Set up the Client to be saved
        $scopeDocument = $collection->createDocument();

        $scopeDocument->setName($name);

        $scopeDocument->setDescription($description);

        $scopeDocument->save();

        return $scopeDocument;
    }

    public function getScopeByName($name)
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');
        $cursor = $collection->find();
        $cursor->where('name', $name);
        $scopeDocument = $cursor->current();

        return $scopeDocument;
    }

    public function getTokenWithOneTimeCode($params)
    {
        $collection = $this->getDocumentManager()->getCollection('oAuthTokens');
        $cursor = $collection->find();

        $cursor->where('code', $params->get('code'));
        $tokenDocument = $cursor->current();

        if (null === $tokenDocument) {
            throw new \Exception('Invalid code specified!', Resource::STATUS_BAD_REQUEST);
        }

        // TODO: This will be removed soon
        $clientDocument = $tokenDocument->client;

        if ($clientDocument->getClientId() !== $params->get('client_id') || $clientDocument->getSecret() !== $params->get('client_secret')) {
            throw new \Exception('Invalid client_id/client_secret combination!', Resource::STATUS_BAD_REQUEST);
        }

        if ($params->get('redirect_uri') !== $clientDocument->getRedirectUri()) {
            throw new \Exception('Redirect_uri mismatch!', Resource::STATUS_BAD_REQUEST);
        }

        //Remove one-time code
        $tokenDocument->setCode(false);
        $tokenDocument->save();
    }
}
