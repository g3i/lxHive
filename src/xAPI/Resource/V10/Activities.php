<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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
use API\Service\Activity as ActivityService;
use API\View\V10\Activity as ActivityView;

class Activities extends Resource
{
    /**
     * @var \API\Service\Activity
     */
    private $activityService;

    /**
     * Get activity service.
     */
    public function init()
    {
        $this->setActivityService(new ActivityService($this->getSlim()));
    }

    // Boilerplate code until this is figured out...
    public function get()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('profile');

        $this->activityService->activityGet($request);

        // Render them
        $view = new ActivityView(['service' => $this->activityService]);

        $view = $view->renderGetSingle();
        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    public function options()
    {
        //Handle options request
        $this->getSlim()->response->headers->set('Allow', 'GET');
        Resource::response(Resource::STATUS_OK);
    }

    /**
     * Gets the value of activityService.
     *
     * @return \API\Service\Activity
     */
    public function getActivityService()
    {
        return $this->activityService;
    }

    /**
     * Sets the value of activityService.
     *
     * @param \API\Service\Activity $activityService the activity service
     *
     * @return self
     */
    public function setActivityService(\API\Service\Activity $activityService)
    {
        $this->activityService = $activityService;

        return $this;
    }
}
