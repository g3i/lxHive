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

namespace API\Controller\V10;

use API\Config;
use API\Bootstrap;
use API\Controller;
use API\View\V10\About as AboutView;

class About extends Controller
{
    /**
     * Compile and render GET /about response
     *
     * @return void
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function get()
    {
        // quick fix for #221, ping DB via a public endpoint
        // throws Exception if database is not up
        // @TODO improve
        $service = new \API\Storage\Adapter\Mongo($this->getContainer());
        $service->getDatabaseversion();

        $versions = Config::get(['xAPI', 'supported_versions']);
        $extensions = $this->getExtensionInfo();
        $core = [
            'lrs' => [
                'name' => Config::get('name'),
                'mode' => Config::get('mode'),
                'version' => Bootstrap::VERSION,
            ]
        ];

        $view = new AboutView($this->getResponse(), $this->getContainer(), [
            'versions' => $versions,
            'extensions' => array_merge($core, $extensions),
        ]);
        $view = $view->render();

        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    /**
     * Compile and render OPTIONS /about response
     *
     * @return void
     */
    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'GET'));
        return $this->response(Controller::STATUS_OK);
    }

    /**
     * Collect info about extensions
     *
     * @return array $info
     */
    public function getExtensionInfo()
    {
        $info = [];
        $installed = Config::get(['extensions']);
        foreach ($installed as $name => $ext) {
            // Precaution in case of mis-configuration
            try {
                if ($ext['enabled']) {
                    $className = $ext['class_name'];
                    $instance = new $className($this->getContainer());
                    $info[$name] = $instance->about();
                }
            } catch (\Exception $e) {
                // Do nothing
            }
            return $info;
        }
    }
}
