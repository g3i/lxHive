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

namespace API\Resource\V10\Agents;

use API\Resource;
use API\Service\AgentProfile as AgentProfileService;
use API\View\V10\AgentProfile as AgentProfileView;

class Profile extends Resource
{
    /**
     * @var \API\Service\AgentProfile
     */
    private $agentProfileService;

    /**
     * Get agent profile service.
     */
    public function init()
    {
        $this->agentProfileService = new AgentProfileService($this->getContainer());
    }

    /**
     * Handle the Statement GET request.
     */
    public function get()
    {
        // Check authentication
        //$this->getContainer()->auth->checkPermission('profile');

        // Do the validation - TODO!!!!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validateGetRequest($request);

        $this->agentProfileService->agentProfileGet();

        // Render them
        $view = new AgentProfileView(['service' => $this->agentProfileService]);

        if ($this->agentProfileService->getSingle()) {
            $view = $view->renderGetSingle();
            Resource::response(Resource::STATUS_OK, $view);
        } else {
            $view = $view->renderGet();
            return $this->jsonResponse(Resource::STATUS_OK, $view);
        }
    }

    public function put()
    {
        // Check authentication
        //$this->getContainer()->auth->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $this->agentProfileService->agentProfilePut();

        //Always an empty response, unless there was an Exception
        return $this->response(Resource::STATUS_NO_CONTENT);
    }

    public function post()
    {
        // Check authentication
        //$this->getContainer()->auth->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $this->agentProfileService->agentProfilePost();

        //Always an empty response, unless there was an Exception
        return $this->response(Resource::STATUS_NO_CONTENT);
    }

    public function delete()
    {
        // Check authentication
        //$this->getContainer()->auth->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $this->agentProfileService->agentProfileDelete();

        //Always an empty response, unless there was an Exception
        return $this->response(Resource::STATUS_NO_CONTENT);
    }

    public function options()
    {
        //Handle options request
        $this->getContainer()->response->headers->set('Allow', 'POST,PUT,GET,DELETE');
        return $this->response(Resource::STATUS_OK);
    }

    /**
     * Gets the value of agentProfileService.
     *
     * @return \API\Service\AgentProfile
     */
    public function getAgentProfileService()
    {
        return $this->agentProfileService;
    }
}
