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
use API\Resource;
use API\Util;
use Slim\Helper\Set;
use Sokil\Mongo\Cursor;

class ActivityState extends Service
{
    // Will be deprecated with ActivityStateResult class
    /**
     * Activity states.
     *
     * @var array
     */
    protected $activityStates;

    // Will be deprecated with ActivityStateResult class
    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

    // Will be deprecated with ActivityStateResult class
    /**
     * Is this a single activity state fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of statement objects.
     */
    public function activityStateGet($request)
    {
        $params = new Set($request->get());

        $cursor = $this->getStorage()->getActivityStateStorage()->getActivityStatesFiltered($params);

        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Tries to save (merge) an activityState.
     */
    public function activityStatePost($request)
    {
        $params = new Set($request->get());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getBody();

        $activityStateDocument = $this->getStorage()->getActivityStateStorage()->postActivityState($params, $rawBody);

        $this->activityStates = [$activityStateDocument];
        $this->single = true;

        return $this;
    }

    /**
     * Tries to PUT (replace) an activityState.
     *
     * @return
     */
    public function activityStatePut($request)
    {
        // Validation has been completed already - everyhing is assumed to be valid (from an external view!)
        $rawBody = $request->getBody();

        // Single
        $params = new Set($request->get());

        $activityStateDocument = $this->getStorage()->getActivityStateStorage()->putActivityState($params, $rawBody);

        $this->single = true;
        $this->activityStates = [$activityStateDocument];

        return $this;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of statement objects.
     */
    public function activityStateDelete($request)
    {
        $params = new Set($request->get());

        $this->getStorage()->getActivityStateStorage()->deleteActivityState($params);

        return $this;
    }

    /**
     * Gets the Activity states.
     *
     * @return array
     */
    public function getActivityStates()
    {
        return $this->activityStates;
    }

    /**
     * Sets the Activity states.
     *
     * @param array $activityStates the activity states
     *
     * @return self
     */
    public function setActivityStates(array $activityStates)
    {
        $this->activityStates = $activityStates;

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
     * Sets the Cursor.
     *
     * @param cursor $cursor the cursor
     *
     * @return self
     */
    public function setCursor(Cursor $cursor)
    {
        $this->cursor = $cursor;

        return $this;
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
