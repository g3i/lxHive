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

namespace API\Service;

use API\Service;
use API\Util\Collection;

class ActivityState extends Service
{
    /**
     * Fetches activity states according to the given parameters.
     *
     * @return array An array of statement objects.
     */
    public function activityStateGet()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        $documentResult = $this->getStorage()->getActivityStateStorage()->getFiltered($params);

        return $documentResult;
    }

    /**
     * Tries to save (merge) an activityState.
     */
    public function activityStatePost()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getRawPayload();

        $params->set('headers', $request->getHeaders());

        $documentResult = $this->getStorage()->getActivityStateStorage()->post($params, $rawBody);

        return $documentResult;
    }

    /**
     * Tries to PUT (replace) an activityState.
     *
     * @return
     */
    public function activityStatePut()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getRawPayload();

        $params->set('headers', $request->getHeaders());

        $documentResult = $this->getStorage()->getActivityStateStorage()->put($params, $rawBody);

        return $documentResult;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @return array An array of statement objects.
     */
    public function activityStateDelete()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        $params->set('headers', $request->getHeaders());

        $this->getStorage()->getActivityStateStorage()->delete($params);

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
