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
use API\Service\Auth as AuthService;
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
        $service = new AuthService($this->getContainer());
        return $service->getAuthScopes();
    }

    /**
     * Fetch all permissions from Mongo
     * @return array collection of permissions with their name as key
     */
    public function mergeInheritedPermissions($names)
    {
        $service = new  AuthService($this->getContainer());
        return $service->mergeInheritance($names);
    }

    /**
     * Add a user record
     * @param string $email
     * @param string $password
     * @param array $selectedPermissions selected scope permission records
     * @return stdClass Mongo user record
     * @throws \API\RuntimeException
     */
    public function addUser($name, $description, $email, $password, $permissions)
    {
        $v = new Validator();
        $v->validateName($name);
        $v->validatePassword($password);

        $this->validateUserEmail($email);

        // fetch available permissions and compare
        $service = new UserService($this->getContainer());
        $user = $service->addUser($name, $description, $email, $password, $permissions);

        return $user;
    }

    /**
     * Get user for objectId
     * @param \MongoDB\BSON\ObjectID $objectId
     * @return stdClass|null
     */
    public function getUser(\MongoDB\BSON\ObjectID $objectId)
    {
        $service = new UserService($this->getContainer());
        return $service->findById($objectId);
    }

    /**
     * Fetch all user email addresses
     *
     * @return array collection of user records with email as key
     */
    public function fetchAllUserEmails()
    {
        // // TODO 0.11.x paginated query
        $userService = new UserService($this->getContainer());
        $documentResult = $userService->fetchAll();
        $users = [];
        foreach ($documentResult->getCursor() as $user) {
            $users[$user->email] = $user;
        }

        return $users;
    }

    /**
     * Validate email address by format and uniqueness
     *  - validate format
     *  - check if email already exists in users
     *
     * @return void
     * @throws AdminException
     */
    public function validateUserEmail($email)
    {
        $v = new Validator();
        $v->validateEmail($email);

        $uservice = new UserService($this->getContainer());
        if ($uservice->hasEmail($email)) {
            throw new AdminException('User email exists already');
        }
    }
}
