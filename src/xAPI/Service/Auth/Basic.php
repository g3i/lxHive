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

namespace API\Service\Auth;

use API\Service;
use API\Controller;
use Slim\Http\Request;
use API\Service\User as UserService;
use API\Util;
use API\HttpException as Exception;
use API\Service\Auth\Exception as AuthFailureException;
use API\Util\Collection;

class Basic extends Service implements AuthInterface
{
    public function addToken($name, $description, $expiresAt, $user, array $scopes = [], $key = null, $secret = null)
    {
        $accessTokenDocument = $this->getStorage()->getBasicAuthStorage()->storeToken($name, $description, $expiresAt, $user, $scopes, $key, $secret);

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
        $accessTokenDocument = $this->getStorage()->getBasicAuthStorage()->getToken($key, $secret);

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
    public function deleteToken($key)
    {
        $this->getStorage()->getBasicAuthStorage()->deleteToken($key);
    }

    /**
     * [expireToken description].
     *
     * @param [type] $clientId    [description]
     * @param [type] $accessToken [description]
     *
     * @return [type] [description]
     */
    public function expireToken($key)
    {
        $accessTokenDocument = $this->getStorage()->getBasicAuthStorage()->expireToken($key);

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
        $cursor = $this->getStorage()->getBasicAuthStorage()->getTokens();

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
        $params = new Collection($request->get());

        $this->fetchToken($params->get('key'), $params->get('secret'));

        return $this;
    }

    /**
     * Tries to create a new access token.
     */
    public function accessTokenPost()
    {
        $body = $this->getContainer()['parser']->getData()->getPayload();

        $requestParams = new Util\Collection($body);

        $this->validateRequiredParams($requestParams);

        $currentDate = new \DateTime();

        $defaultParams = new Util\Collection([
            'user' => [
                'name' => 'anonymous',
                'description' => '',
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

        $params = new Util\Collection(array_replace_recursive($defaultParams->all(), $requestParams->all()));

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
        $userService = new UserService($this->getContainer());
        $user = $userService->addUser($params->get('user')['name'], $params->get('user')['description'], $params->get('user')['email'], $params->get('user')['password'], $permissionDocuments);

        $accessTokenDocument = $this->addToken($params->get('name'), $params->get('description'), $expiresAt, $user, $scopeDocuments);

        return $accessTokenDocument;
    }

    /**
     * Tries to delete an access token.
     */
    public function accessTokenDelete($request)
    {
        $params = new Collection($request->get());

        $this->deleteToken($params->get('key'), $params->get('secret'));

        return $this;
    }

    public function extractToken(Request $request)
    {
        $header = $request->getHeaderLine('Authorization');
        if (!$header) {
            throw new AuthFailureException('Authorization header required.');
        }

        if (preg_match('/Basic\s+(.*)$/i', $header, $matches)) {
            list($authUser, $authPass) = explode(':', base64_decode($matches[1]));
        } else {
            throw new AuthFailureException('Authorization header invalid.');
        }

        $components = explode(':', $str);
        $authUser = $components[0];
        $authPass = (isset($components[1])) ? $components[1] : '';


        try {
            $token = $this->fetchToken($authUser, $authPass);
        } catch (\Exception $e) {
            throw new AuthFailureException('Authorization header invalid.');
        }

        return $this;
    }

    private function validateJsonDecodeErrors()
    {
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in existing document. Cannot merge!', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateRequiredParams($requestParams)
    {
        if ($requestParams['user']['email'] === null) {
            throw new Exception('Invalid request, user.email property not present!', Controller::STATUS_BAD_REQUEST);
        }
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
