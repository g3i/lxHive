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

namespace API\Storage\Query;

interface UserInterface extends QueryInterface
{

    /**
     * Find record by Mongo ObjectId
     * @param string $id
     *
     * @return \API\DocumentInterface|null
     */
    public function findById($id);

    /**
     * Add a user
     * The only validation we do at this level is ensuring that the email is unique
     *
     * @param string $name
     * @param string $description
     * @param string $email valid email address
     * @param string $password
     * @param array  $permissions valid array of permission records
     *
     * @throws Exception
     */
    public function addUser($name, $user, $email, $password, $permissions);

    /**
     * Find all records
     * @return \API\DocumentInterface
     */
    public function fetchAll();

    /**
     * Check if collection contains a user with a specified email
     * @param string $email
     *
     * @return bool
     */
    public function hasEmail($email);

    /**
     * Fetch a user record for specified email and password
     *
     * @param string $username
     * @param string $password
     *
     * @return \API\DocumentInterface|null
     */
    public function findByEmailAndPassword($username, $password);
}
