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

namespace API\Resource\V10\Activities;

use API\Resource;
use API\Service\ActivityProfile as ActivityProfileService;
use API\View\V10\ActivityProfile as ActivityProfileView;

class Profile extends Resource
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
        $this->setActivityProfileService(new ActivityProfileService($this->getSlim()));
    }

    /**
     * Handle the Statement GET request.
     */
    public function get()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('profile');

        // Do the validation - TODO!!!!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validateGetRequest($request);

        $this->activityProfileService->activityProfileGet($request);

        // Render them
        $view = new ActivityProfileView(['service' => $this->activityProfileService]);

        if ($this->activityProfileService->getSingle()) {
            $view = $view->renderGetSingle();
            Resource::response(Resource::STATUS_OK, $view);
        } else {
            $view = $view->renderGet();
            Resource::jsonResponse(Resource::STATUS_OK, $view);
        }
    }

    public function put()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $this->activityProfileService->activityProfilePut($request);

        //Always an empty response, unless there was an Exception
        Resource::response(Resource::STATUS_NO_CONTENT);
    }

    public function post()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $this->activityProfileService->activityProfilePost($request);

        //Always an empty response, unless there was an Exception
        Resource::response(Resource::STATUS_NO_CONTENT);
    }

    public function delete()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('profile');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Save the statements
        $this->activityProfileService->activityProfileDelete($request);

        //Always an empty response, unless there was an Exception
        Resource::response(Resource::STATUS_NO_CONTENT);
    }

    public function options()
    {
        //Handle options request
        $this->getSlim()->response->headers->set('Allow', 'POST,PUT,GET,DELETE');
        Resource::response(Resource::STATUS_OK);
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

    /**
     * Sets the value of activityProfileService.
     *
     * @param \API\Service\ActivityProfile $activityProfileService the activity service
     *
     * @return self
     */
    public function setActivityProfileService(\API\Service\ActivityProfile $activityProfileService)
    {
        $this->activityProfileService = $activityProfileService;

        return $this;
    }
}
