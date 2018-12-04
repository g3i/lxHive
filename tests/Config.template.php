<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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
namespace Tests;

/**
 * Configuration for unit tests
 */
class Config
{
    /**
     * @var array $lrs remote lrs configuration
     */
    public static $lrs = [
        'production' => [
            'baseuri' => '<baseuri>',               // base endpoint without trailing slash, e.g. http://sandbox.experience.at
            'version' => '1.0.2',                   // targeted xAPI version
            'user' => '<basicauth:username>',       // basicAuth: username
            'password' => '<basicauth_password>',   // basicAuth: password
        ],
        'development' => [
            'baseuri' => '<baseuri>',               // base endpoint without trailing slash, e.g. http://sandbox.experience.at
            'version' => '1.0.2',                   // targeted xAPI version
            'user' => '<basicauth:username>',       // basicAuth: username
            'password' => '<basicauth_password>',   // basicAuth: password
        ]
    ];
}
