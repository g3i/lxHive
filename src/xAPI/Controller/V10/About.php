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

use API\Controller;
use API\View\V10\About as AboutView;
use API\Config;

class About extends Controller
{
    /**
     * compile and render GET /about response
     *
     * @return void
     */
    public function get()
    {
        $versions = Config::get(['xAPI', 'supported_versions']);
        $extensions = $this->getExtensionInfo();

        $view = new AboutView($this->getResponse(), $this->getContainer(), [
            'versions' => $versions,
            'extensions' => $extensions
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
        foreach ($installed as $ext) {
            try { // precaution in case of mis-configuration
                if ($ext['enabled']) {
                    $class_name = $ext['class_name'];
                    $instance = new $class_name ($this->getContainer());
                    $info[] = $instance->about();
                }
            } catch (\Exception $e) {
                // nothing
            }
            return $info;
        }
    }
}
