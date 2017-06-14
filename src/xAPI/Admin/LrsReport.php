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
use API\Container;
use API\Storage\Adapter\Mongo as Mongo;

/**
 * LRS Status Report
 */
class LrsReport
{
    private $reports = [];

    private $count = [
        'total' => 0,
        'completed' => 0,
    ];

    /**
     * @constructor
     */
    public function __construct()
    {
        if (!Bootstrap::mode()) {
            $bootstrap = Bootstrap::factory(Bootstrap::Config);
        }
    }

    /**
     * run comprehensive check
     * @return array report
     */
    public function check()
    {
        $ok = $this->checkConfigYml();
        $this->count($ok);

        if ($ok) {
            $ok = $this->checkConfigYml();
        }
        $this->count($ok);

        if ($ok) {
            $ok = $this->checkMongo();
        }
        $this->count($ok);

        if ($ok) {
            $ok = $this->checkDataBase();
        }
        $this->count($ok);

        if ($ok) {
            $ok = $this->checkUsersAndPermissions();
        }
        $this->count($ok);

        if ($ok) {
            $ok = $this->checkLocalFileStorage();
        }
        $this->count($ok);

        //TODO: check statements and document stats
        return $this->reports;
    }

    /**
     * compute summary of performed checks
     * @return array summary
     */
    public function summary()
    {
        $summary = array_merge($this->count, [
            'reports' => [
                'success' => 0,
                'error' => 0,
                'warn' => 0,
                'total' => 0,
            ]
        ]);

        foreach ($this->reports as $section => $report) {
            foreach ($report as $label => $item) {
                switch ($item['status']) {
                    case 'success': {
                        $summary['reports']['success']++;
                        break;
                    }
                    case 'error': {
                        $summary['reports']['error']++;
                        break;
                    }
                    case 'warn': {
                        $summary['reports']['warn']++;
                        break;
                    }
                }
                $summary['reports']['total']++;
            }
        }

        //TODO: check statements and document stats
        return $summary;
    }

    /**
     * run basic checks on configuration yaml files
     * @return bool indicator if tests were completed
     */
    private function checkConfigYml()
    {
        $setup = new Setup();

        $config = [];
        try {
            $config = $setup->loadYaml('Config.yml');
        } catch (AdminException $e) {
            $this->error('Config', 'Config.yml', $e->getMessage());
            return false;
        }
        $this->success('Config', 'Config.yml');

        $data = [];
        try {
            $data = $setup->loadYaml('Config.production.yml');
            $this->success('Config', 'Config.production.yml');
        } catch (AdminException $e) {
            $this->error('Config', 'Config.production.yml', $e->getMessage());
        }

        $data = [];
        try {
            $data = $setup->loadYaml('Config.development.yml');
            $this->success('Config', 'Config.development.yml');
        } catch (AdminException $e) {
            $this->warn('Config', 'Config.development.yml', $e->getMessage());
        }

        return true;
    }

    /**
     * run basic checks on Mongo connection
     * @return bool indicator if tests were completed
     */
    private function checkMongo()
    {
        $setup = new Setup();

        $conn = Config::get(['storage', 'Mongo', 'host_uri']);
        $version = $setup->testDbConnection($conn);

        if (false === $version) {
            $this->error('Mongo', 'connection', $conn.' not a valid Mongo connection');
            return false;
        } else {
            $this->success('Mongo', 'connection', $version);
        }

        return true;
    }

    /**
     * run basic checks/stats on Mongo DB
     * @return bool indicator if tests were completed
     */
    private function checkDataBase()
    {
        $mongo = new Mongo(new Container());
        $cursor = $mongo->executeCommand(['dbStats' => 1, 'scale' => 1024 * 1024]);
        $result = $cursor->toArray()[0];

        $this->notice('Mongo', 'database', $result->db);

        $this->notice('Mongo', 'collections', $this->numberFormat($result->collections));
        $this->notice('Mongo', 'objects', $this->numberFormat($result->objects));
        $this->notice('Mongo', 'dataSize', $this->numberFormat($result->dataSize, 'Mb'));
        $this->notice('Mongo', 'storageSize', $this->numberFormat($result->storageSize, 'Mb'));
        $this->notice('Mongo', 'fileSize', $this->numberFormat($result->storageSize, 'Mb'));

        return true;
    }

    /**
     * run basic checks and stats on stored permissions, users, and tokens
     * @return bool indicator if tests were completed
     */
    private function checkUsersAndPermissions()
    {
        $mongo = new Mongo(new Container());

        $count = $mongo->count(Mongo\AuthScopes::COLLECTION_NAME);
        if (!$count) {
            $this->error('Collections', 'authScopes', 'No Authentication Scopes', 'LRS setup is incomplete');
        } else {
            $this->success('Collections', 'authScopes', $count);
        }

        $count = $mongo->count(Mongo\User::COLLECTION_NAME);
        if (!$count) {
            $this->error('Collections', 'users', 'No users', 'LRS is not accessible');
        } else {
            $this->success('Collections', 'users', $count);
        }

        $count = $mongo->count(Mongo\BasicAuth::COLLECTION_NAME);
        if (!$count) {
            $this->warn('Collections', 'basicTokens', 'No basic tokens', 'LRS is not accessible via HTTP basic');
        } else {
            $this->success('Collections', 'basicTokens', $count);
        }

        $count = $mongo->count(Mongo\OAuthClients::COLLECTION_NAME);
        if (!$count) {
            $this->warn('Collections', 'oAuthClients', 'No oAuth clients', 'LRS is not accessible via oAuth');
        } else {
            $this->success('Collections', 'oAuthClients', $count);
        }

        $count = $mongo->count(Mongo\OAuth::COLLECTION_NAME);
        $this->notice('Collections', 'oAuthTokens', $count);

        return true;
    }

    /**
     * run basic checks and stats on local file storage
     * @return bool indicator if tests were completed
     */
    private function checkLocalFileStorage()
    {
        $root = Config::get(['publicRoot'], ''.time());
        $dir  = Config::get(['filesystem', 'local', 'root_dir'], ''.time());
        $path = $root.'/'.$dir;

        $abspath = realpath($path);

        if (false == $abspath) {
            $this->error('FileStorage', 'local', 'directory not found or not readable', $path);
            return false;
        }

        $size = $this->dirSize($abspath);
        $this->success('FileStorage', 'local', $this->numberFormat($size/ (1024 * 1024), 'Mb'), $abspath);
        return true;
    }

    private function notice($section, $label, $value, $note = '')
    {
        $this->set($section, $label, 'notice', $value, $note);
    }

    private function success($section, $label, $value = 'ok', $note = '')
    {
        $this->set($section, $label, 'success', $value, $note);
    }

    private function warn($section, $label, $value, $note = '')
    {
        $this->set($section, $label, 'warn', $value, $note);
    }

    private function error($section, $label, $value, $note = '')
    {
        $this->set($section, $label, 'error', $value, $note);
    }

    /**
     * register a report
     * @param string $section section
     * @param string $label section label
     * @param string $status  [success, error, warn, notice]
     * @param string $value message
     * @param string $note
     *
     * @return void
     */
    private function set($section, $label, $status, $value, $note = '')
    {
        if (!isset($this->reports[$section])) {
            $this->reports[$section] = [];
        }
        if (!isset($this->reports[$section][$label])) {
            $this->reports[$section][$label] = [];
        }

        $this->reports[$section][$label] = [
            'status' => $status,
            'value' => $value,
            'note' => $note,
        ];
    }

    /**
     * count a report result
     * @param bool $ok
     *
     * @return void
     */
    private function count($ok)
    {
        $this->count['total']++;
        $this->count['completed'] += (int) $ok;
    }

    /**
     * formats a float number (english notation, 2 decimals)
     * @param mixed $val
     * @param string $unit suffix
     * @return string
     */
    public function numberFormat($val, $unit = null)
    {
        $unit = ($unit) ? ' '.$unit : '';
        return number_format((float)$val, 2, '.', '').$unit;
    }

    /**
     * compute directory size recursively
     * @return int bytes
     */
    public function dirSize($path)
    {
        $total = 0;
        $path = realpath($path);
        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                ) as $object) {
                $total += $object->getSize();
            }
        }
        return $total;
    }
}
