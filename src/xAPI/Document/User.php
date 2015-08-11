<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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

namespace API\Document;

use Sokil\Mongo\Document;
use API\Resource;
use API\Document\Auth\Scope;

class User extends Document implements \JsonSerializable
{
    protected $_data = [
        'email'            => null,
        'passwordHash'     => null,
    ];

    public function relations()
    {
        return [
            'basicAuthTokens' => [self::RELATION_HAS_MANY, 'basicAuthTokens', 'userId'],
            'oAuthTokens' => [self::RELATION_HAS_MANY, 'oAuthTokens', 'userId'],
            'permissions' => [self::RELATION_MANY_MANY, 'authScopes', 'permissionIds', true],
        ];
    }

    public function addPermission(Scope $scope)
    {
        $this->addRelation('permissions', $scope);
    }

    public function isSuperUser()
    {
        return $this->hasPermission('super');
    }

    public function hasPermission($permissionName)
    {
        foreach ($this->permissions as $permission) {
            if ($permission->getName() === $permissionName || $permission->getName() === 'super') {
                return true;
            }
        }

        return false;
    }

    public function checkPermission($permissionName)
    {
        if ($this->hasPermission($permissionName)) {
            return true;
        } else {
            return new \Exception('Permission denied.', Resource::STATUS_FORBIDDEN);
        }
    }

    public function renderSummary()
    {
        $return = ['email' => $this->_data['email'], 'permissions' => array_values($this->permissions)];

        return $return;
    }

    public function jsonSerialize()
    {
        return $this->_data;
    }
}
