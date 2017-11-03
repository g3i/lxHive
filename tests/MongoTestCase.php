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
namespace Tests;

use PHPUnit_Framework_TestCase as BaseTestCase;

use Symfony\Component\Yaml\Yaml;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;
use MongoDB\Client;

use API\Bootstrap;
use API\Config;

class MongoTestCase extends TestCase
{

    const DB = 'LXHIVE_UNITTEST';

    /**
     *  @var Manager $client MongoDB Manger instance
     */
    protected static $client;

    /**
     * Called before the first test of the test case class is run
     * Caches default LRS database name from config
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::reset();
        Bootstrap::factory(Bootstrap::Testing);
        self::$client = new Manager(Config::get(['storage', 'Mongo', 'host_uri']));

        // make sure db exists by creating and removing a dummy collection
        $name = 'ping';
        $cmd = new Command([ 'create' => $name ]);
        $res = self::$client->executeCommand(self::DB, $cmd);

        $cmd = new Command([ 'drop' => $name ]);
        $res = self::$client->executeCommand(self::DB, $cmd);
    }

    ////
    // API
    ////

    /**
     * Execute a Mongo command on test database
     * @see http://php.net/manual/en/mongodb-driver-manager.executecommand.php
     * @param string $collection collection name
     * @param array $filter
     * @param array $options
     *
     * @return Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function command(array $command) {
        $cmd = new Command($command);
        $cursor = self::$client->executeCommand(self::DB, $cmd);
        return $cursor;
    }

    /**
     * Execute a Mongo query on test database
     * @see http://php.net/manual/en/mongodb-driver-manager.executequery.php
     * @param string $collection collection name
     * @param array $filter
     * @param array $options
     *
     * @return Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function query(string $collection, array $filter, array $options = []) {
        $query = new Query($filter, $options);
        $cursor = self::$client->executeQuery(self::DB.'.'.$collection, $query);
        return $cursor;
    }

    /**
     * Execute a Mongo bulkwrite on test database
     * @see http://php.net/manual/en/mongodb-driver-manager.executebulkwrite.php
     * @param BulkWrite $bulkWrite MongoDb Driver BulkWrite instance
     *
     * @return MongoDB\Driver\WriteResult
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function bulkWrite(string $collection, BulkWrite $bulkWrite) {
        $result = self::$client->executeBulkWrite(self::DB.'.'.$collection, $bulkWrite);
        return $result;
    }

    /**
     * Drops a collection
     * @see https://docs.mongodb.com/manual/reference/command/drop/
     *
     * @return Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function dropCollection(string $name) {
        try {
            $cursor = $this->command([ 'drop' => $name ]);
            return $cursor;
        } catch(\MongoDB\Driver\Exception\RuntimeException $e) {
            // MongoDB throws a very broad MongoDB\Driver\Exception\RuntimeException: "ns not found", if collection doesn't exist
            // ...which is not nice as Mongo manual says that it returns "...false when collection to drop does not exist."
            return false;
        }
    }

    /**
     * Drops a database
     * @see https://docs.mongodb.com/manual/reference/command/dropDatabase/
     *
     * @return Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function dropDatabase() {
        $cursor = $this->command([ 'dropDatabase' => 1 ]);
        return $cursor;
    }
}
