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
use API\Storage\Adapter\Base;

class User extends Base implements UserInterface
{
    public function findByEmailAndPassword($username, $password)
    {
        $collection = $this->getDocumentManager()->getCollection('users');
        $cursor = $collection->find();

        $cursor->where('email', $params->get('email'));
        $cursor->where('passwordHash', sha1($params->get('password')));

        $document = $cursor->current();

        return $document;
    }

    public function findById($id)
    {
        $collection = $this->getDocumentManager()->getCollection('users');

        $result = $collection->getDocument($id);

        return $result;
    }

    public function addUser($email, $password, $permissions)
    {
        $storage = $this->getContainer()['storage'];
        $collection = 'users';

        // Set up the User to be saved
        $userDocument = new \API\Document\Generic();

        $userDocument->setEmail($email);

        $passwordHash = sha1($password);
        $userDocument->setPasswordHash($passwordHash);

        /*foreach ($permissions as $permission) {
            $userDocument->addPermission($permission);
        }*/

        $storage->insertOne($collection, $userDocument);

        return $userDocument;
    }

    public function fetchAll()
    {
        $collection = 'users';
        $storage = $this->getContainer()['storage'];
        $cursor = $storage->find($collection);

        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }

    public function fetchAvailablePermissions()
    {
        $collection = 'authScopes';
        $storage = $this->getContainer()['storage'];

        $cursor = $storage->find($collection);

        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }
}
