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

use API\Bootstrap;
use API\Config;

/**
 * Admin validator functions
 */
class Validator
{

    /**
     * constructor
     */
    public function __construct()
    {
        if (!Bootstrap::mode()) {
            $bootstrap = Bootstrap::factory(Bootstrap::Config);
        }
    }

    /**
     * Validate password
     * @param string $str
     *
     * @return void
     * @throws AdminException
     */
    public function validatePassword(string $str)
    {
        $errors = [];
        $length = 8;

        if (strlen($str) < $length) {
            $errors[] = 'Must have at least '.$length.' characters';
        }

        if (!preg_match('/[0-9]+/', $str)) {
            $errors[] = 'Must include at least one number.';
        }

        if (!preg_match('/[a-zA-Z]+/', $str)) {
            $errors[] = 'Must include at least one letter.';
        }

        if( !preg_match('/[A-Z]+/', $str) ) {
            $errors[] = 'Must include at least one CAPS!';
        }

        if( !preg_match('/\W+/', $str) ) {
            $errors[] = 'Must include at least one symbol!';
        }

        if(!empty($errors)) {
            throw new AdminException(json_encode($errors));
        }

    }

    /**
     * Validate email address
     * @param string $email
     *
     * @return void
     * @throws AdminException
     */
    public function validateEmail(string $email)
    {
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new AdminException('Invalid email address!');
        }
    }

    /**
     * Validate xAPI permission scopes
     * @param array $perms array of strings: permissions to check
     * @param array $available array of strings: available permissions to check against
     *
     * @return void
     * @throws AdminException
     */
    public function validateXapiPermissions(array $perms, array $available)
    {
        if(empty($perms)) {
            throw new AdminException('Permissions cannot be empty.');
        }

        if(empty($available)) {
            throw new AdminException('Available permissions cannot be empty.');
        }

        foreach ($perms as $p) {
            if(!in_array($p, $available)) {
                throw new AdminException('Invalid permission');
            }
        }
    }
}
