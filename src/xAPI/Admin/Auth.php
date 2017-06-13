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
use API\Admin;

/**
 * Auth Managment
 */
class Auth extends Admin
{
    /**
     * Fetches a list of all oAuth Clients
     * @return array
     */
    public function listOAuthClients()
    {
        $oAuthService = new OAuthService($this->getContainer());

        $documentResult = $oAuthService->fetchClients();

        $textArray = $documentResult->getCursor()->toArray();

        return $textArray;
    }

    /**
     * Add an oAuth client record
     * @param string $name
     * @param string $description
     * @param string $redirectUri
     */
    public function addOAuthClient($name, $description, $redirectUri)
    {
        $oAuthService = new OAuthService($this->getContainer());
        $client = $oAuthService->addClient($name, $description, $redirectUri);

        return $client;
    }

    /**
     * Fetches a list of all basic tokens
     * @return array
     */
    public function listBasicTokens()
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->fetchTokens();

        $textArray = [];
        foreach ($accessTokenService->getCursor() as $document) {
            $textArray[] = $document;
        }

        return $textArray;
    }

    /**
     * Fetches a list of all basic token id's
     * @return array
     */
    public function listBasicTokenIds()
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->fetchTokens();
        $keys = [];
        foreach ($accessTokenService->getCursor() as $document) {
            $keys[] = $document->key;
        }

        return $keys;
    }

    /**
     * Expire a basic token
     * @param string $clientId valid clientId
     * @return void
     */
    public function expireBasicToken($key)
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->expireToken($key);
    }

    /**
     * Deleta a basic token
     * @param string $clientId valid clientId
     * @return void
     */
    public function deleteBasicToken($key)
    {
        $accessTokenService = new BasicAuthService($this->getContainer());

        $accessTokenService->deleteToken($key);
    }

    /**
     * Create a new Authscope record
     * @param string $name scope name/identifier
     * @param string $description
     * @return \stdClass Mongo entry
     */
    public function createAuthScope($name, $description)
    {
        $oAuthService = new OAuthService($this->getContainer());

        $scope = $oAuthService->addScope($name, $description);

        return $scope;
    }

    /**
     * Add a new basic Token
     * @param string $name
     * @param string $description
     * @param int $expiresAt Unix timestamp
     * @param string $user user id
     * @param array $selectedScopes scope records
     * @param string $key
     * @param string $secret
     */
    public function addToken($name, $description, $expiresAt, $user, $selectedScopes, $key, $secret)
    {
        $basicAuthService = new BasicAuthService($this->getContainer());

        $token = $basicAuthService->addToken($name, $description, $expiresAt, $user, $selectedScopes, $key, $secret);

        return $token;
    }
}
