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

namespace API\Admin;

use API\Service\Auth\OAuth as OAuthService;
use API\Service\Auth\Basic as BasicAuthService;

class Auth extends Base
{
    public function listOAuthClients()
    {
        $oAuthService = new OAuthService($this->getContainer());

        $documentResult = $oAuthService->fetchClients();

        $textArray = $documentResult->getCursor()->toArray();

        return $textArray;
    }

    public function addOAuthClient($name, $description, $redirectUri)
    {
        $oAuthService = new OAuthService($this->getContainer());
        $client = $oAuthService->addClient($name, $description, $redirectUri);

        return $client;
    }

    public function listBasicTokens()
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->fetchTokens();

        $textArray = [];
        foreach ($accessTokenService->getCursor() as $document) {
            $textArray[] = $document->jsonSerialize();
        }

        return $textArray;
    }

    public function listBasicTokenIds()
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->fetchTokens();
        $clientIds = [];
        foreach ($accessTokenService->getCursor() as $document) {
            $clientIds[] = $document->getClientId();
        }

        return $clientIds;
    }

    public function expireBasicToken($clientId)
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->expireToken($clientId);
    }

    public function deleteBasicToken($clientId)
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->deleteToken($clientId);
    }

    public function createAuthScope($name, $description)
    {
        $oAuthService = new OAuthService($this->getContainer());

        $scope = $oAuthService->addScope($name, $description);

        return $scope;
    }

    public function addToken($name, $description, $expiresAt, $user, $selectedScopes, $key, $secret)
    {
        $basicAuthService = new BasicAuthService($this->getContainer());

        $token = $basicAuthService->addToken($name, $description, $expiresAt, $user, $selectedScopes, $key, $secret);

        return $token;
    }
}
