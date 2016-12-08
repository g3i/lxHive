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

use API\Storage\Query\BasicAuthInterface;
use API\Resource;
use API\HttpException as Exception;

class BasicAuth extends Base implements BasicAuthInterface
{
    public function storeToken($name, $description, $expiresAt, $user, $scopes)
    {
        $collection = $this->getDocumentManager()->getCollection('basicTokens');

        $accessTokenDocument = $collection->createDocument();

        $accessTokenDocument->setName($name);
        $accessTokenDocument->setDescription($description);
        $accessTokenDocument->addRelation('user', $user);
        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope->getId();
        }
        $accessTokenDocument->setScopeIds($scopeIds);

        if (isset($expiresAt)) {
            $expiresDate = new \DateTime();
            $expiresDate->setTimestamp($expiresAt);
            $accessTokenDocument->setExpiresAt(\API\Util\Date::dateTimeToMongoDate($expiresDate));
        }

        //Generate token
        $accessTokenDocument->setKey(\API\Util\OAuth::generateToken());
        $accessTokenDocument->setSecret(\API\Util\OAuth::generateToken());

        $currentDate = new \DateTime();
        $accessTokenDocument->setCreatedAt(\API\Util\Date::dateTimeToMongoDate($currentDate));

        $accessTokenDocument->save();

        return $accessTokenDocument;
    }

    public function getToken($key, $secret)
    {
        $collection = $this->getDocumentManager()->getCollection('basicTokens');
        $cursor = $collection->find();

        $cursor->where('key', $key);
        $cursor->where('secret', $secret);
        $accessTokenDocument = $cursor->current();

        if ($accessTokenDocument === null) {
            throw new \Exception('Invalid credentials.', Resource::STATUS_FORBIDDEN);
        }

        $expiresAt = $accessTokenDocument->getExpiresAt();

        if ($expiresAt !== null) {
            if ($expiresAt->sec <= time()) {
                throw new \Exception('Expired token.', Resource::STATUS_FORBIDDEN);
            }
        }
    }

    public function deleteToken($clientId)
    {
        $collection = $this->getDocumentManager()->getCollection('basicTokens');

        $expression = $collection->expression();

        $expression->where('clientId', $clientId);

        $collection->deleteDocuments($expression);
    }

    public function expireToken($clientId, $accessToken)
    {
        $collection = $this->getDocumentManager()->getCollection('basicTokens');
        $cursor = $collection->find();

        $cursor->where('token', $accessToken);
        $cursor->where('clientId', $clientId);
        $accessTokenDocument = $cursor->current();
        $accessTokenDocument->setExpired(true);
        $accessTokenDocument->save();

        return $accessTokenDocument;
    }

    public function fetchTokens()
    {
        $collection = $this->getDocumentManager()->getCollection('basicTokens');
        $cursor = $collection->find();
    }

    public function getScopeByName($name)
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');
        $cursor = $collection->find();
        $cursor->where('name', $name);
        $scopeDocument = $cursor->current();

        if (null === $scopeDocument) {
            throw new \Exception('Invalid scope given!', Resource::STATUS_BAD_REQUEST);
        }

        return $scopeDocument;
    }
}
