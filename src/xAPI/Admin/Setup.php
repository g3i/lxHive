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
            $bootstrap = Bootstrap::factory(Bootstrap::None);
            $config = $bootstrap->initConfig();
        }
        $this->configDir = Config::get('appRoot').'/src/xAPI/Config/';
    }

    /**
     * Checks if a yaml config file exists already in /src/xAPI/Config/.
     *
     * @param string $configYML yaml file
     * @return bool
     */
    public function checkYaml($configYML)
    {
        return file_exists($configYML = $this->configDir.'/'.$configYML);
    }

    /**
     * creates a config yml file in /src/xAPI/Config/ from an existing template, merges data with template data.
     *
     * @param string $yaml      yaml file to be created from template
     * @param array  $mergeData associative array of config data to be merged in to the new config file
     * @throws \Exception
     */
    public function installYaml($yml, array $mergeData = [])
    {
        $configYML = $this->configDir.'/'.$yml;
        $templateYML = $this->configDir.'/Templates/'.$yml;

        $template = file_get_contents($templateYML);
        if (false === $template) {
            throw new \Exception('Error reading file `'.$templateYML.'` Make sure the file exists and is readable.');
        }
        $data = Yaml::parse($template, true);// exceptionOnInvalidType
        if (!empty($mergeData)) {
            $data = array_merge($data, $mergeData);
        }
        $this->yamlData = $data;
        $ymlData = Yaml::dump($data, 3, 4);// exceptionOnInvalidType
        if (false === file_put_contents($configYML, $ymlData)) {
            throw new \Exception('Error rwriting '.__DIR__.'/../Config/'.$configYML.' Make sure the directory is writable.');
        }
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
