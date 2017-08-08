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
use API\Service\Auth as AuthService;
use API\Util;
use API\Util\Collection;

use API\HttpException as Exception;
use API\Service\Auth\Exception as AuthFailureException;


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

        return $cursor;
    }

    /**
     * get scope document by scope name
     */
    public function getScopeByName($name)
    {
        $scope = $this->getStorage()->getAuthScopesStorage()->findByName($name);
        return $scope;
    }

    /**
     * Tries to get an access token.
     */
    public function accessTokenGet($request)
    {
        $params = new Collection($request->get());
        $token = $this->fetchToken($params->get('key'), $params->get('secret'));

        return $token;
    }

    /**
     * Tries to create a new access token.
     */
    public function accessTokenPost()
    {
        // TODO: This isn't okay, as we are switching to objects everywhere. However, there is not nice way to "merge" objects in PHP...
        $body = json_decode($this->getContainer()->get('parser')->getData()->getRawPayload(), true);
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
        $permissionService = new AuthService($this->getContainer());

        // sanitize submitted token permissions
        $scopes = $params->get('scopes');
        $scopeDocuments = $permissionService->filterPermissions($scopes);

        // sanitize submitted user permissions
        $permissions = $params->get('user')['permissions'];
        $permissionDocuments = $permissionService->filterPermissions($scopes);

        // TODO compare user vs token permissions. token permissions can be only a sub-set or equal user permissions

        if (is_numeric($params->get('expiresAt'))) {
            $expiresAt = $params->get('expiresAt');
        } elseif (null === $params->get('expiresAt')) {
            $expiresAt = null;
        } else {
            $expiresAt = new \DateTime($params->get('expiresAt'));
            $expiresAt = $expiresAt->getTimestamp();
        }

        // TODO: This is ugly, remove this! @sraka, less ugly if separated in two functions, for user, for token :)
        $userService = new UserService($this->getContainer());
        $user = $userService->addUser($params->get('user')['name'], $params->get('user')['description'], $params->get('user')['email'], $params->get('user')['password'], $permissionDocuments)->toArray();
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
            $str =  base64_decode($matches[1]);
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

        return $token;
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
}
