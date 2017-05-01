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

namespace API\Storage\Adapter\Mongo;

use API\Storage\Query\UserInterface;
use API\Storage\Provider;

class User extends Provider implements UserInterface
{
    const COLLECTION_NAME = 'users';

    public function findByEmailAndPassword($username, $password)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('email', $params->get('email'));
        $expression->where('passwordHash', sha1($params->get('password')));

        $document = $storage->findOne(self::COLLECTION_NAME, $expression);

        return $document;
    }

    public function findById($id)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('_id', $id);

        $result = $storage->findOne($id);

        return $result;
    }

    public function addUser($email, $password, $permissions)
    {
        $storage = $this->getContainer()['storage'];

        // Set up the User to be saved
        $userDocument = new \API\Document\Generic();

        $userDocument->setEmail($email);

        $passwordHash = sha1($password);
        $userDocument->setPasswordHash($passwordHash);

        // Permission is of type Scope
        $permissionIds = [];
        foreach ($permissions as $permission) {
            // Fetch the permission ID's and assign them
            $permissionIds[] = $permission['_id'];
            $userDocument->setPermissionIds($permissionIds);
        }

        $storage->insertOne(self::COLLECTION_NAME, $userDocument);

        return $userDocument;
    }

    public function fetchAll()
    {
        $storage = $this->getContainer()['storage'];
        $cursor = $storage->find(self::COLLECTION_NAME);

        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }

    public function fetchAvailablePermissions()
    {
        $storage = $this->getContainer()['storage'];

        $cursor = $storage->find(self::COLLECTION_NAME);

        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }
}
