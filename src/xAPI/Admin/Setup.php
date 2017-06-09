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

use Symfony\Component\Yaml\Yaml;
use API\Service\Auth\OAuth as OAuthService;
use API\Storage\Adapter\Mongo as Mongo;

use API\Bootstrap;
use API\Config;

/**
 * Scratch-Api for various Admin tasks who are not dependend on a bootstrapped application
 */
class Setup
{
    /**
     * @var string configDir path to config folder
     */
    private $configDir;

    /**
     * @var string yamlData internal cache for config.yaml data
     */
    private $yamlData;

    /**
     * constructor
     */
    public function __construct()
    {
        if (!Bootstrap::mode()) {
            $bootstrap = Bootstrap::factory(Bootstrap::Config);
        }
        $this->configDir = Config::get('appRoot').'/src/xAPI/Config/';
    }

    /**
     * Checks if a yaml config file exists already in /src/xAPI/Config/.
     *
     * @param string $configYML yaml file
     *
     * @return string|false
     */
    public function locateYaml($yml)
    {
        clearstatcache();
        return realpath($this->configDir.'/'.$yml);
    }

    /**
     * Loads a config yml file in /src/xAPI/Config/.
     *
     * @param string $yaml yaml file to be created from template
     * @returns array $data associative array of parsed data
     *
     * @return array $data
     * @throws AdminException
     */
    public function loadYaml($yml)
    {
        $file = $this->locateYaml($yml);
        if (false === $file) {
            throw new AdminException('File `'.$yml.'` not found.');
        }

        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new AdminException('Error reading file `'.$yml.'` Make sure the file exists and is readable.');
        }

        try {
            $data = Yaml::parse($contents, true);
        } catch (\Exception $e) {
            // @see \Symfony\Component\Yaml\Yaml::parse()
            throw new AdminException('Error parsing data from file `'.$yml.'`');
        }

        if (!$data) {
            throw new AdminException('Error parsing data from file `'.$yml.'`: Empty data.');
        }

        return $data;
    }

    /**
     * Creates a config yml file in /src/xAPI/Config/ from an existing template, merges data with template data.
     *
     * @param string $yml      yaml file to be created from template
     * @param array $mergeData associative array of config data to be merged in to the new config file
     * @return array $data
     * @throws \Exception
     */
    public function installYaml($yml, array $mergeData = [])
    {
        if ($this->locateYaml($yml)) {
            throw new AdminException('File `'.$yml.'` exists already. The LRS configuration would be overwritten. To restore the defaults you must manually remove the file first.');
        }

        $data = $this->loadYaml('Templates/'.$yml);
        if (!empty($mergeData)) {
            $data = array_merge($data, $mergeData);
        }

        $file = $this->configDir.'/'.$yml;
        $contents = Yaml::dump($data, 3, 4);// exceptionOnInvalidType
        if (false === file_put_contents($file, $contents)) {
            throw new AdminException('Error writing file `'.$file.'` Make sure the directory is writable.');
        }

        return $data;
    }

    /**
     * creates a config yml file in /src/xAPI/Config/ from an existing template, merges data with template data.
     *
     * @param string $yaml      yaml file to be created from template
     * @param array  $update associative array of config data to be merged in to the new config file
     *
     * @return array $data
     * @throws \Exception
     */
    public function updateYaml($yml, array $update)
    {
        $file = $this->locateYaml($yml);
        $data = $this->loadYaml($yml);

        $data = array_merge($data, $update);
        $contents = Yaml::dump($data, 3, 4);// exceptionOnInvalidType
        if (false === file_put_contents($file, $contents)) {
            throw new AdminException('Error updating file `'.$file.'` Make sure the directory is writable.');
        }
        return $data;
    }

    /**
     * Test Mongo DB access
     * @param  string $uri connection uri
     * @return stdClass|false connection result (mongo.buildInfo)
     */
    public function testDbConnection($uri)
    {
        try {
            $connectionTestResult = Mongo::testConnection($uri);
            return $connectionTestResult;
        } catch (\MongoDB\Driver\Exception\ConnectionException $e) {
            return false;
        }
    }

    /**
     * Load authscopes into Mongo
     * @return void
     */
    public function initializeAuthScopes()
    {
        //TODO this method will be obsolete if we remove the authScopes collection
        $bootstrap = Bootstrap::factory(Bootstrap::Console);
        $container = $bootstrap->initCliContainer();
        $oAuthService = new OAuthService($container);

        foreach (Config::get(['xAPI', 'supported_auth_scopes']) as $authScope) {
            $scope = $oAuthService->addScope($authScope['name'], $authScope['description']);
        }
    }
}
