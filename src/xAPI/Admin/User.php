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

use API\Service\User as UserService;

class User extends Base
{
    public function fetchAvailablePermissions()
    {
        $userService = new UserService($this->getContainer());
        $documentResult = $userService->fetchAvailablePermissions();
        $permissionsDictionary = [];
        foreach ($documentResult->getCursor() as $permission) {
            $permissionsDictionary[$permission['name']] = $permission;
        }

        return $permissionsDictionary;
    }

    public function addUser($email, $password, $selectedPermissions)
    {
        $userService = new UserService($this->getContainer());
        $user = $userService->addUser($email, $password, $selectedPermissions);

        return $user;
    }

    public function fetchAllUserEmails()
    {
        $userService = new UserService($this->getContainer());
        $documentResult = $userService->fetchAll();
        $users = [];
        foreach ($documentResult->getCursor() as $user) {
            $users[$user['email']] = $user;
        }

        return $users;
    }
}
