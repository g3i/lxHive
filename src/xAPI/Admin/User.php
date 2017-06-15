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
use API\Admin;

/**
 * User managment
 */
class User extends Admin
{
    /**
     * Fetch all permissions from Mongo
     * @return array collection of permissions with their name as key
     */
    public function fetchAvailablePermissions()
    {
        //TODO this method will be obsolete if we remove the authScopes collection
        $userService = new UserService($this->getContainer());
        $documentResult = $userService->fetchAvailablePermissions();

        $permissionsDictionary = [];
        foreach ($documentResult->getCursor() as $permission) {
            $permissionsDictionary[$permission->name] = $permission;
        }

        return $permissionsDictionary;
    }

    /**
     * Fetch all permissions from Mongo
     * @return array collection of permissions with their name as key
     */
    public function fetchAvailablePermissionNames()
    {
        //TODO this method will be obsolete if we remove the authScopes collection
        $service = new UserService($this->getContainer());
        $document = $service->fetchAvailablePermissionNames();
        return current($document->getCursor()->toArray())->values;
    }

    /**
     * Add a user record
     * @param string $email
     * @param string $password
     * @param array $selectedPermissions selected scope permission records
     * @return stdClass Mongo user record
     * @throws \API\RuntimeException
     */
    public function addUser($name, $description, $email, $password, $selectedPermissions)
    {
        $v = new Validator();
        $v->validateName($name);
        $v->validatePassword($password);

        $this->validateEmail($email);

        // fetch available permissions and compare
        $service = new UserService($this->getContainer());
        $result = $service->fetchPermissionsByNames($selectedPermissions);
        $permissions = $result->getCursor()->toArray();
        if (count($permissions) !== count($selectedPermissions)) {
            throw new AdminException('Invalid permissions: '.json_encode($selectedPermissions));
        }

        $user = $service->addUser($name, $description, $email, $password, $permissions);

        return $user;
    }

    /**
     * Fetch all user email addresses
     * TODO, make scalable (search)
     * @return array collection of user records with email as key
     */
    public function fetchAllUserEmails()
    {
        $userService = new UserService($this->getContainer());
        $documentResult = $userService->fetchAll();
        $users = [];
        foreach ($documentResult->getCursor() as $user) {
            $users[$user->email] = $user;
        }

        return $users;
    }

    /**
     * Fetch all user email addresses
     * TODO, make scalable (search)
     * @return array collection of user records with email as key
     */
    public function validateEmail($email)
    {
        $v = new Validator();
        $v->validateEmail($email);

        $uservice = new UserService($this->getContainer());
        if($uservice->hasEmail($email)){
            throw new AdminException('User email exists already');
        }
    }
}
