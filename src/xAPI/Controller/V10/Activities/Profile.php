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

namespace API\Controller\V10\Activities;

use API\Controller;
use API\Service\ActivityProfile as ActivityProfileService;
use API\View\V10\ActivityProfile as ActivityProfileView;

class Profile extends Controller
{
    /**
     * @var \API\Service\ActivityProfile
     */
    private $activityProfileService;

    /**
     * Get activity service.
     */
    public function init()
    {
        $this->activityProfileService = new ActivityProfileService($this->getContainer());
    }

    /**
     * Handle the Statement GET request.
     */
    public function get()
    {
        // Check authentication
        $this->getContainer()->get('auth')->checkPermission('profile');

        // Do the validation - TODO!!!!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validateGetRequest($request);

        $documentResult = $this->activityProfileService->activityProfileGet();

        // Render them
        $view = new ActivityProfileView($this->getResponse(), $this->getContainer());

        if ($documentResult->getIsSingle()) {
            $view = $view->renderGetSingle($documentResult);
            return $this->response(Controller::STATUS_OK, $view);
        } else {
            $view = $view->renderGet($documentResult);
            return $this->jsonResponse(Controller::STATUS_OK, $view);
        }
    }

    public function put()
    {
        // Check authentication
        $this->getContainer()->get('auth')->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $documentResult = $this->activityProfileService->activityProfilePut();

        //Always an empty response, unless there was an Exception
        return $this->response(Controller::STATUS_NO_CONTENT);
    }

    public function post()
    {
        // Check authentication
        $this->getContainer()->get('auth')->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $documentResult = $this->activityProfileService->activityProfilePost();

        //Always an empty response, unless there was an Exception
        return $this->response(Controller::STATUS_NO_CONTENT);
    }

    public function delete()
    {
        // Check authentication
        $this->getContainer()->get('auth')->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $deletionResult = $this->activityProfileService->activityProfileDelete();

        //Always an empty response, unless there was an Exception
        return $this->response(Controller::STATUS_NO_CONTENT);
    }

    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'POST,PUT,GET,DELETE'));
        return $this->response(Controller::STATUS_OK);
    }

    /**
     * Gets the value of activityProfileService.
     *
     * @return \API\Service\ActivityProfile
     */
    public function getActivityProfileService()
    {
        return $this->activityProfileService;
    }
}
