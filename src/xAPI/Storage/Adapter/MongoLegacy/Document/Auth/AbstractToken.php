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

namespace API\Storage\Adapter\MongoLegacy\Document\Auth;

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

            if ($permissionName !== 'super' && $scope->getName() === 'all') {
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
        if ($this->getExpiresAt() === null) {
            return;
        } else {
            $dateTime->setTimestamp($this->getExpiresAt()->sec);
            $until = \API\Util\Date::secondsUntil($dateTime);

            return $until;
        }
    }

    public function setExpiresIn($expiresIn)
    {
        $until = \API\Util\Date::dateFromSeconds($expiresIn);
        $until = \API\Util\Date::dateStringToMongoDate($until);
        $this->setExpiresAt($until);

        return $this;
    }

    public function isExpired()
    {
        if ($this->getExpired()) {
            return true;
        } elseif (null !== $this->getExpiresIn() && $this->getExpiresIn() <= 0) {
            $this->setExpired(true);

            return true;
        } else {
            return false;
        }
    }

    public function jsonSerialize()
    {
        return $this->_data;
    }
}
