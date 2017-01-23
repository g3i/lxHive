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

namespace API\Resource\V10;

use API\Resource;
use Slim\Helper\Set;
use API\View\V10\Agent as AgentView;

class Agents extends Resource
{
    public function get()
    {
        $request = $this->getContainer()->request();

        // Check authentication
        $this->getContainer()->auth->checkPermission('profile');

        // TODO: Validation.

        $params = new Set($request->get());

        $agent = $params->get('agent');

        $agent = json_decode($agent, true);

        $view = new AgentView(['agent' => $agent]);
        $view = $view->renderGet();

        return $this->jsonResponse(Resource::STATUS_OK, $view);
    }

    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'GET'));
        return $this->response(Resource::STATUS_OK);
    }
}
