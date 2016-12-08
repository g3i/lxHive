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

namespace API\Service\Auth;

use API\Service;
use API\Resource;
use Slim\Helper\Set;
use Slim\Http\Request;
use API\Service\User as UserService;
use API\Util;

class Basic extends Service implements AuthInterface
{
    /**
     * Access tokens.
     *
     * @var array
     */
    protected $accessTokens;

    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

    /**
     * Is this a single access token fetch?
     *
     * @var bool
     */
    protected $single = false;

    public function addToken($name, $description, $expiresAt, $user, array $scopes = [])
    {
        $accessTokenDocument = $this->getStorage()->getBasicAuthStorage()->storeToken($name, $description, $expiresAt, $user, $scopes);

        $this->single = true;
        $this->setAccessTokens([$accessTokenDocument]);

        return $accessTokenDocument;
    }

    /**
     * [fetchToken description].
     *
     * @param [type] $key    [description]
     * @param [type] $secret [description]
     *
     * @return [type] [description]
     */
    public function fetchToken($key, $secret)
    {
        $accessTokenDocument = $this->getStorage()->getBasicAuthStorage()->fetchToken($key, $secret);

        $this->setAccessTokens([$accessTokenDocument]);

        return $accessTokenDocument;
    }

    /**
     * [deleteToken description].
     *
     * @param [type] $clientId [description]
     *
     * @return [type] [description]
     */
    public function deleteToken($clientId)
    {
        $this->getStorage()->getBasicAuthStorage()->deleteToken($clientId);
    }

    /**
     * [expireToken description].
     *
     * @param [type] $clientId    [description]
     * @param [type] $accessToken [description]
     *
     * @return [type] [description]
     */
    public function expireToken($clientId, $accessToken)
    {
        $accessTokenDocument = $this->getStorage()->getBasicAuthStorage()->expireToken($clientId, $accessToken);

        $this->setAccessTokens([$accessTokenDocument]);

        return $accessTokenDocument;
    }

    /**
     * [fetchTokens description].
     *
     * @return [type] [description]
     */
    public function fetchTokens()
    {
        $cursor = $this->getStorage()->getBasicAuthStorage()->fetchTokens();

        $this->setCursor($cursor);

        return $this;
    }

    // REDUNDANT!
    public function getScopeByName($name)
    {
        $scope = $this->getStorage()->getBasicAuthStorage()->getScopeByName($name);
        return $scope;
    }

    /**
     * Tries to get an access token.
     */
    public function accessTokenGet($request)
    {
        $params = new Set($request->get());

        $this->fetchToken($params->get('key'), $params->get('secret'));

        return $this;
    }

    /**
     * Tries to create a new access token.
     */
    public function accessTokenPost($request)
    {
        $body = $request->getBody();
        $body = json_decode($body, true);

        // Some clients escape the JSON - handle them
        if (is_string($body)) {
            $body = json_decode($body, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON posted. Cannot continue!', Resource::STATUS_BAD_REQUEST);
        }

        $requestParams = new Set($body);

        if ($requestParams->get('user')['email'] === null) {
            throw new \Exception('Invalid request, user.email property not present!', Resource::STATUS_BAD_REQUEST);
        }

        $currentDate = new \DateTime();

        $defaultParams = new Set([
            'user' => [
                'password' => 'password',
                'permissions' => [
                    'all',
                ],
            ],
            'scopes' => [
                'all',
            ],
            'name' => 'Token for '.$requestParams->get('user')['email'],
            'description' => 'Token generated at '.Util\Date::dateTimeToISO8601($currentDate),
            'expiresAt' => null,
        ]);

        $params = new Set(array_replace_recursive($defaultParams->all(), $requestParams->all()));

        $scopeDocuments = [];
        $scopes = $params->get('scopes');
        foreach ($scopes as $scope) {
            $scopeDocument = $this->getScopeByName($scope);
            $scopeDocuments[] = $scopeDocument;
        }

        $permissionDocuments = [];
        $permissions = $params->get('user')['permissions'];
        foreach ($permissions as $permission) {
            $permissionDocument = $this->getScopeByName($permission);
            $permissionDocuments[] = $permissionDocument;
        }

        if (is_numeric($params->get('expiresAt'))) {
            $expiresAt = $params->get('expiresAt');
        } elseif (null === $params->get('expiresAt')) {
            $expiresAt = null;
        } else {
            $expiresAt = new \DateTime($params->get('expiresAt'));
            $expiresAt = $expiresAt->getTimestamp();
        }

        // This is ugly, remove this!
        $userService = new UserService($this->getSlim());
        $user = $userService->addUser($params->get('user')['email'], $params->get('user')['password'], $permissionDocuments);

        $this->addToken($params->get('name'), $params->get('description'), $expiresAt, $user, $scopeDocuments);

        return $this;
    }

    /**
     * Tries to delete an access token.
     */
    public function accessTokenDelete($request)
    {
        $params = new Set($request->get());

        $this->deleteToken($params->get('key'), $params->get('secret'));

        return $this;
    }

    public function extractToken(Request $request)
    {
        $headers = $request->headers();
        $rawHeaders = $request->rawHeaders();
        if (isset($rawHeaders['Authorization'])) {
            $header = $rawHeaders['Authorization'];
        } elseif (isset($headers['Authorization'])) {
            $header = $headers['Authorization'];
        } else {
            throw new Exception('Authorization header required.');
        }

        if (preg_match('/Basic\s+(.*)$/i', $header, $matches)) {
            list($authUser, $authPass) = explode(':', base64_decode($matches[1]));
        } else {
            throw new Exception('Authorization header invalid.');
        }

        if (isset($authUser) && isset($authPass)) {
            try {
                $token = $this->fetchToken($authUser, $authPass);
            } catch (\Exception $e) {
                throw new Exception('Authorization header invalid.');
            }
        }

        return $token;
    }

    /**
     * Gets the Access tokens.
     *
     * @return array
     */
    public function getAccessTokens()
    {
        return $this->accessTokens;
    }

    /**
     * Sets the Access tokens.
     *
     * @param array $accessTokens the access tokens
     *
     * @return self
     */
    public function setAccessTokens(array $accessTokens)
    {
        $this->accessTokens = $accessTokens;

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
    public function setCursor($cursor)
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Gets the Is this a single access token fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single access token fetch?.
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
