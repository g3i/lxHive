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
        $body = $this->getContainer()->get('parser')->getData()->getPayload();
        $this->validateRequiredParams($body);
        $currentDate = new \DateTime();

        $parsedParams = (object)[
            'user' => [
                'name' => isset($body->user->name) ? $body->user->name : 'anonymous',
                'description' => isset($body->user->description) ? $body->user->description : '',
                'password' => isset($body->user->password) ? $body->user->password : 'password',
                'permissions' => isset($body->user->permissions) ? $body->user->permissions :  ['all'],
            ],
            'scopes' => isset($body->scopes) ? $body->scopes : ['all'],
            'name' => 'Token for '.$body->user->email,
            'description' => 'Token generated at '.Util\Date::dateTimeToISO8601($currentDate),
            'expiresAt' => isset($body->expiresAt) ? $body->expiresAt : null,
        ];

        $permissionService = new AuthService($this->getContainer());

        // Sanitize submitted token permissions
        $scopes = $parsedParams->scopes;
        $scopeDocuments = $permissionService->sanitizePermissions($scopes);

        // Sanitize submitted user permissions
        $permissions = $parsedParams->user->permissions;
        $permissionDocuments = $permissionService->sanitizePermissions($permissions);

        // You cannot create a user with more permissions than the user associated with the API call
        $callingTokenPermissions = $this->getContainer()->get('auth')->getPermissions();

        // User-permisisons can only be a subset of callingTokenPermissions
        if (!(array_intersect($parsedParams->user->permissions, $callingTokenPermissions) == $parsedParams->user->permissions)) {
            // $parsedParams->scopes is not a subset of $parsedParams->user->permissions
            throw new Exception('Permissions array cannot contain more permissions that the used accessToken associated users\' permissions!', Controller::STATUS_BAD_REQUEST);
        }

        // Scopes can only be a subset of user->permissions
        if (!(array_intersect($parsedParams->scopes, $parsedParams->user->permissions) == $parsedParams->scopes)) {
            // $parsedParams->scopes is not a subset of $parsedParams->user->permissions
            throw new Exception('Scopes array cannot contain more permissions that the associated users\' permissions!', Controller::STATUS_BAD_REQUEST);
        }

        // Temporary super tokens cannot be created
        if (in_array('super', $parsedParams->scopes) || in_array('super', $parsedParams->scopes)) {
            throw new Exception('Tokens and users with super permissions cannot be created using this endpoint!', Controller::STATUS_BAD_REQUEST);
        }

        if (is_numeric($parsedParams->expiresAt)) {
            $expiresAt = $parsedParams->expiresAt;
        } elseif (null === $parsedParams->expiresAt) {
            $expiresAt = null;
        } else {
            $expiresAt = new \DateTime($parsedParams->expiresAt);
            $expiresAt = $expiresAt->getTimestamp();
        }

        // TODO 0.11.x: This functionality (user creation + token creation should be in two separate API calls)
        $userService = new UserService($this->getContainer());
        $user = $userService->addUser($parsedParams->user->name, $parsedParams->user->description, $parsedParams->user->email, $parsedParams->user->password, $permissionDocuments)->toArray();
        $accessTokenDocument = $this->addToken($parsedParams->name, $parsedParams->description, $expiresAt, $user, $scopeDocuments);

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
        if (!isset($requestParams->user->email) || $requestParams->user->email === null) {
            throw new Exception('Invalid request, user.email property not present!', Controller::STATUS_BAD_REQUEST);
        }
    }
}
