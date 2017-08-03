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

use API\Config;
use API\Bootstrap;
use API\Storage\Adapter\Mongo as Mongo;
use API\Service\Auth\OAuth as OAuthService;

use API\Storage\AdapterException;
use MongoDB\Driver\Exception\Exception as MongoException;

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
            throw new AdminException('Error reading file `'.$yml.'`. Make sure the file exists and is readable.');
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
     * Deletes a yaml file. Throws an Exception on failure
     * @param string $yml      yaml file to be created from template
     *
     * @return void
     * @throws AdminException
     */
    public function removeYaml($yml)
    {
        if (!$this->locateYaml($yml)) {
            return;
        }

        $file = $this->configDir.'/'.$yml;
        if (false === unlink($file)) {
            throw new AdminException('Error deleting `'.$file.'`. Make sure the directory is writable.');
        }
    }

    /**
     * Creates a config yml file in /src/xAPI/Config/ from an existing template, merges data with template data.
     * @param string $yml      yaml file to be created from template
     * @param array $mergeData associative array of config data to be merged in to the new config file
     *
     * @return array $data
     * @throws AdminException
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
     * @param string $yaml      yaml file to be created from template
     * @param array  $update associative array of config data to be merged in to the new config file
     *
     * @return array $data
     * @throws AdminException
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
     *
     * @return stdClass|false connection result (mongo.buildInfo)
     */
    public function testDbConnection($uri)
    {
        // \MongoDB\Driver\Manager will use mongodb://127.0.0.1/ when no or empty uri was submitted
        if (!$uri) {
            return false;
        }

        try {
            $buildInfo = Mongo::testConnection($uri);
            return $buildInfo;
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            return false;
        } catch (\MongoDB\Driver\Exception\ConnectionException $e) {
            return false;
        }
    }

    /**
     * Test Mongo DB access
     * @param  string $uri connection uri
     *
     * @return string version info if versions are compatible
     * @throws AdminException if installed version is lower than required
     */
    public function verifyDbVersion($container = null)
    {
        if (!$container) {
            $container = Bootstrap::getContainer();
        }
        $mongo = new Mongo($container);

        try {
            $info = $mongo->verifyDatabaseVersion();
        } catch (AdapterException $e) {
            throw new AdminException($e->getMessage());
        }
        return sprintf('Available: "%s", Required: "%s"', $info['installed'], $info['required']);
    }

    /**
     * Install Database
     *
     * @return void
     * @throws AdminException
     */
    public function installDb($container = null)
    {
        if (!$container) {
            $container = Bootstrap::getContainer();
        }

        $schema = new Mongo\Schema($container);

        try {
            $schema->install();
        } catch (MongoException $e) {
            throw new AdminException('Error installing Database. Error: '. $e->getMessage());
        } catch (AdapterException $e) {
            throw new AdminException('Error installing Database. Error: '. $e->getMessage());
        }
    }


    /**
     * Creates writable storage directories for files (attachments) and logs
     *
     * @return \SplFileInfo
     * @throws \Exception
     */
    public function installFileStorage()
    {
        $root = realpath(Config::get('appRoot'));
        if (!$root) {
            throw new AdminException('Error installing local FS: Missing Config[appRoot]');
        }

        $dir = $root.'/storage';
        if (false === $this->createStorageDir($dir)) {
            throw new AdminException('Unable to create folder: '.$dir);
        }

        $dir = $root.'/storage/files';
        if (false === $this->createStorageDir($dir)) {
            throw new AdminException('Unable to create folder: '.$dir);
        }

        $dir = $root.'/storage/logs';
        if (false === $this->createStorageDir($dir)) {
            throw new AdminException('Unable to create folder: '.$dir);
        }

        return new \SplFileInfo($root.'/storage');
    }

    /**
     * Creates Storage dir with approbiate permissions
     * @param string $dir (real) path to dir to create
     *
     * @return bool
     */
    private function createStorageDir($dir)
    {
        if (is_dir($dir)) {
            return true;
        }
        return @mkdir($dir, 755);// surpress PHP warning in console
    }
}
