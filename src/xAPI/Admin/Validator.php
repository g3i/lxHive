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
     * Validate name param
     * @param string $str
     *
     * @return void
     * @throws AdminException
     */
    public function validateName($str)
    {
        if (!is_string($str)) {
            throw new AdminException('Must be a string');
        }

        $errors = [];
        $length = 4;

        if (!$str || strlen($str) < $length) {
            $errors[] = 'Must have at least '.$length.' characters';
        }

        if (!empty($errors)) {
            throw new AdminException(json_encode($errors));
        }
    }

    /**
     * Validate password
     * @param string $str
     *
     * @return void
     * @throws AdminException
     */
    public function validatePassword($str)
    {
        if (!is_string($str)) {
            throw new AdminException('Must be a string');
        }

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

        if (!preg_match('/[A-Z]+/', $str)) {
            $errors[] = 'Must include at least one CAPS!';
        }

        if (!preg_match('/\W+/', $str)) {
            $errors[] = 'Must include at least one symbol!';
        }

        if (!empty($errors)) {
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
    public function validateEmail($email)
    {

        if (!is_string($email)) {
            throw new AdminException('Must be a string');
        }
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
        if (empty($perms)) {
            throw new AdminException('Permissions cannot be empty.');
        }

        if (empty($available)) {
            throw new AdminException('Available permissions cannot be empty.');
        }

        $aNames = array_keys($available);

        foreach ($perms as $name) {
            if (!in_array($name, $aNames)) {
                throw new AdminException('Invalid permission');
            }
        }
    }

    /**
     * Validate an absolute url, require at least scheme and host
     *
     * @return void
     * @throws AdminException
     */
    public function validateRedirectUri($str)
    {

        if (!is_string($str)) {
            throw new AdminException('Must be a string');
        }

        $components = parse_url($str);
        if (false === $components) {
            throw new AdminException('Invalid url');
        }

        if (!isset($components['scheme'])) {
            throw new AdminException('Redirect url requires a valid scheme');
        }

        if (!isset($components['host'])) {
            throw new AdminException('Redirect url requires a valid host component');
        }
    }

    /**
     * Validate Mongo Naming (database and collection name)
     * @see https://docs.mongodb.com/manual/reference/limits/
     *
     * @return void
     * @throws AdminException
     */
    public function validateMongoName($str)
    {

        if (!is_string($str)) {
            throw new AdminException('Must be a string');
        }

        $errors = [];
        $minLength = 4;// mongo does only require a length > 0
        $maxLength = 64;

        if (!$str || strlen($str) < $minLength) {
            $errors[] = 'Must have at least '.$minLength.' characters';
        }

        if (!$str || strlen($str) > $maxLength) {
            $errors[] = 'Must less than '.$maxLength.' characters';
        }

        if (!preg_match('/^[a-z0-9_\-]+$/i', $str)) {
            $errors[] = 'Can only contain letter, numbers, dashes and underscores';
        }

        if (!empty($errors)) {
            throw new AdminException(json_encode($errors));
        }
    }
}
