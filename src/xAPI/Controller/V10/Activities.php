<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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
use API\Service\Activity as ActivityService;
use API\View\V10\Activity as ActivityView;

class Activities extends Controller
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
        $this->activityService = new ActivityService($this->getContainer());
    }

    // Boilerplate code until this is figured out...
    public function get()
    {
        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('profile');

        $activityDocument = $this->activityService->activityGet();

        // Render them
        $view = new ActivityView($this->getResponse(), $this->getContainer());

        $view = $view->renderGetSingle($activityDocument);
        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'GET'));
        return $this->response(Controller::STATUS_OK);
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
}
