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

namespace API\Service;

use API\Service;
use Slim\Helper\Set;

class ActivityProfile extends Service
{
    // Will be deprecated with ActivityProfileResult class
    /**
     * Activity profiles.
     *
     * @var array
     */
    protected $activityProfiles;

    // Will be deprecated with ActivityProfileResult class
    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

    // Will be deprecated with ActivityProfileResult class
    /**
     * Is this a single activity state fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * Fetches activity profiles according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of activityProfile objects.
     */
    public function activityProfileGet($request)
    {
        $params = new Set($request->get());

        $cursor = $this->getStorage()->getActivityProfileStorage()->getActivityProfilesFiltered($params);

        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Tries to save (merge) an activityProfile.
     */
    public function activityProfilePost($request)
    {
        $params = new Set($request->get());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getBody();

        $params->set('headers', $request->headers());

        $agentProfileDocument = $this->getStorage()->getActivityProfileStorage()->postActivityProfile($params, $rawBody);

        $this->single = true;
        $this->activityProfiles = [$activityProfileDocument];

        return $this;
    }

    /**
     * Tries to PUT (replace) an activityState.
     *
     * @return
     */
    public function activityProfilePut($request)
    {
        // Validation has been completed already - everyhing is assumed to be valid (from an external view!)
        $rawBody = $request->getBody();

        // Single
        $params = new Set($request->get());

        $params->set('headers', $request->headers());

        $agentProfileDocument = $this->getStorage()->getActivityProfileStorage()->putActivityProfile($params, $rawBody);

        $this->single = true;
        $this->activityProfiles = [$activityProfileDocument];

        return $this;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return self Nothing.
     */
    public function activityProfileDelete($request)
    {
        $params = new Set($request->get());

        $params->set('headers', $request->headers());

        $this->getStorage()->getActivityProfileStorage()->deleteActivityProfile($params);

        return $this;
    }

    /**
     * Gets the Activity states.
     *
     * @return array
     */
    public function getActivityProfiles()
    {
        return $this->activityProfiles;
    }

    /**
     * Sets the Activity profiles.
     *
     * @param array $activityProfiles the activity profiles
     *
     * @return self
     */
    public function setActivityProfiles(array $activityProfiles)
    {
        $this->activityProfiles = $activityProfiles;

        return $this;
    }

    /**
     * Gets the Cursor.
     *
     * @return cursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Gets the Is this a single activity state fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single activity state fetch?.
     *
     * @param bool $single the is single
     *
     * @return self
     */
    public function setSingle($single)
    {
        $this->single = $single;

        return $this;
    }
}
