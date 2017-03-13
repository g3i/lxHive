<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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

namespace API\Util;

use \Sokil\Mongo\Client;
use API\Resource;

class MongoClient
{
    /**
     * @var \Sokil\Mongo\Client $client mongo client instance
     */
    public $client;

    /**
     * @var \Sokil\Mongo\Database $database mongo database instance
     */
    public $db;

    public function __construct($config)
    {
        $this->client = new Client($config['database']['host_uri']);
        $this->db = $this->client->getDatabase($config['database']['db_name']);
    }
}
