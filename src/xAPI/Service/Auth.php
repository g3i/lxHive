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

namespace API\Service;

use API\Service;
use API\Config;
use API\Bootstrap;

use API\HttpException;

/**
 * Inheritance writes the inherited permissions into the token and user records AS IT IS ON TIME OF CREATION,
 * Applying changed permissions (config) requires to re-issue the token
 *
 *      Flat permissions: Permission inheritances of the config are merged into a flat, unique array of permissions inside the token or user document.
 *      Creation time only: a user / token permissions are created on date of creation and don't change when the config was changed
 *      One level inheritance only: Inheritance is a first-level only merge. It doesn\'t include childs of childs (only first children)
 *      Unknown permissions in user/token stored documents are ignored
 *
 * We do not considder inheritance changes on run-time as this can be troublesome.
 * Such a dynamic permission assignment is a ROLE behaviour and planned for a future release.
 * Roles are understood as goups of permissions who can be updated at any time an reflect the changes.
*/
class Auth extends Service
{
    /**
     * @var string $userId user Mongo ObjectId
     */
    private $userId = null;

    /**
     * @var array $permissions current token/auth permissions, not to be confused with global user permissions
     */
    private $permissions = [];

    /**
     * @var array $scopes Available permission scopes (cached)
     */
    private $scopes;

    /**
     * @constructor
     */
    public function __construct($container, $authScopes = null)
    {
        parent::__construct($container);
        $this->scopes = Config::get(['xAPI', 'supported_auth_scopes'], []);
    }


    /**
     * Mock AuthScopes for unit testing
     *
     * @return string|Null Mongo ObjectId
     */
    public function mockAuthScopes(array $scopes)
    {
        if(Bootstrap::mode() !== Bootstrap::Testing) {
            throw new \RunTimeException('Mocking AuthScopes is not allowed in this Bootstrap Mode');
        }
        $this->scopes = $scopes;
    }

    /**
     * Register a user auth, user Id and permissions
     * @param string|\MongoDB\BSON\ObjectID  $userId user record _id
     * @param array $scopeIds token names as string
     *
     * @return void
     */
    public function register($userId, array $permissionNames)
    {
        $this->userId = (string) $userId;

        // map storerd token permissions against current configuration
        // filter out token permissions who are not part of the current configuration
        $filtered = $this->filterPermissions($permissionNames);

        // we do not update inheritance here
        $this->permissions = $filtered;
        // $this->permissions = $this->mergeInheritance($filtered);
    }

    /**
     * Gets the current UserId
     *
     * @return string|Null Mongo ObjectId
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Gets the current permissions
     *
     * @return array permission names
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Merges inherited permissions into an array of given permission names
     * Invalid permission names (not in config) are ignored.
     * @see self::$scopes
     * @param array $permissions array of permission names
     *
     * @return string|Null Mongo ObjectId
     */
    public function mergeInheritance(array $permissions)
    {
        $merged = $permissions;
        foreach($permissions as $name) {
            $merged = array_merge($merged, $this->getInheritanceFor($name));
        }
        return array_unique($merged);
    }

    /**
     * Get inherited permissions for a sinfgle permissiion
     * An invalid permission name (not in config) will be ignored.
     * @see self::$scopes
     * @param string $name permission name
     *
     * @return array inherited permissions (not including $name!)
     */
    public function getInheritanceFor(string $name)
    {
        if (!isset($this->scopes[$name])) {
            return [];
        }
        if(empty($this->scopes[$name]['inherits'])){
            return [];
        }
        // yaml parses empty arrays to {}
        return (array) $this->scopes[$name]['inherits'];
    }

    /**
     * Gets the registered AuthScopes
     *
     * @return array
     */
    public function getAuthScopes()
    {
        return $this->scopes;
    }

    /**
     * Gets single AuthScope by permission name
     *
     * @return array|false
     */
    public function getAuthScope(string $name)
    {
        return (isset($this->scopes[$name])) ? $this->scopes[$name] : false;
    }

    /**
     * Checks if a permission is set for the current user auth
     *
     * @return bool
     */
    public function hasPermission(string $name)
    {
        return in_array($name, $this->permissions);
    }

    /**
     * Checks if a permission is set for the user auth and throws Exception
     * if queried permission is not assigned to the user auth
     *
     * @throws HttpException when unauthorized
     */
    public function requirePermission(string $name)
    {
        $permissions = $this->mergeInheritance($this->permissions);
        
        if (!in_array($name, $permissions)){
            throw new HttpException('Unauthorized', 401);
        }

        // this was mapped in constructor already however in this case it's better to check twice
        if (!isset($this->scopes[$name])){
            throw new HttpException('Unauthorized', 401);
        }
    }

    /**
     * Public alias for self::filterPermissions
     * @param array $permissionNames
     *
     * @return array registered and valid permissions
     *
     */
    public function sanitizePermissions(array $permissionNames)
    {
        return $this->filterPermissions($permissionNames);
    }

    /**
     * Filters and sanitizes submitted permission names against configured permission names
     * @param array $permissionNames
     *
     * @return array registered and valid permissions
     *
     */
    private function filterPermissions(array $permissionNames)
    {
        $configured = array_keys($this->scopes);
        return array_filter($permissionNames, function($name) use ($configured) {
            // TODO 0.10.x Issue warning to logger
            if(!is_string($name)) {
                return false;
            }
            if(empty($name)) {
                return false;
            }
            return in_array($name, $configured);
            // TODO 0.10.x Issue warning
        });
    }

}
