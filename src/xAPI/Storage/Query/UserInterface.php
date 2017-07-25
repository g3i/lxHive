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
    public function findByEmailAndPassword($username, $password);

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

    public function fetchAll();

    public function hasEmail($email);
}
