<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2016 Brightcookie Pty Ltd
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

abstract class Base
{	
	private $container; 

	// Bootstrapped container
    public function __construct($container)
    {
    	$this->container = $container;
    }

    /**
     * Gets the value of container.
     *
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * checks if a yaml config file exists already in /src/xAPI/Config/.
     *
     * @param string $configYML yaml file
     *
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
     *
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
            $data += $mergeData;
        }
        $ymlData = Yaml::dump($data, 3, 4);// exceptionOnInvalidType
        if (false === file_put_contents($configYML, $ymlData)) {
            throw new \Exception('Error rwriting '.__DIR__.'/../Config/'.$configYML.' Make sure the directory is writable.');
        }
    }
}
