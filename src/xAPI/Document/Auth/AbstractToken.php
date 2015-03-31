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

namespace API\Document\Auth;

use Sokil\Mongo\Document;
use API\Resource;

abstract class AbstractToken extends Document implements \JsonSerializable, TokenInterface
{
    public function addScope(Scope $scope)
    {
        $this->addRelation('scopes', $scope);
    }

    public function isSuperToken()
    {
        return $this->hasPermission('super');
    }

    public function hasPermission($permissionName)
    {
        foreach ($this->scopes as $scope) {
            if ($scope->getName() === $permissionName || $scope->getName() === 'super') {
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

    public function getExpiresIn()
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($this->getExpiresAt());
        $until = \API\Util\Date::secondsUntil($dateTime);

        return $until;
    }

    public function setExpiresIn($expiresIn)
    {
        $until = \API\Util\Date::dateFromSeconds($expiresIn);
        $this->setExpiresAt($until);

        return $this;
    }

    public function isExpired()
    {
        if ($this->getExpired()) {
            return true;
        } elseif ($this->getExpiresIn === 0) {
            $this->setExpired(true);

            return true;
        } else {
            return false;
        }
    }

    public function isValid()
    {
        if ($this->isExpired()) {
            return false;
        } else {
            return true;
        }
    }

    public function jsonSerialize()
    {
        return $this->_data;
    }
}
