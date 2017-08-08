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

namespace API\Service;

use API\Service;
use API\Util\Collection;

class ActivityProfile extends Service
{
    /**
     * Fetches activity profiles according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of activityProfile objects.
     */
    public function activityProfileGet()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        $documentResult = $this->getStorage()->getActivityProfileStorage()->getFiltered($params);

        return $documentResult;
    }

    /**
     * Tries to save (merge) an activityProfile.
     */
    public function activityProfilePost()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getRawPayload();

        $params->set('headers', $request->getHeaders());

        $documentResult = $this->getStorage()->getActivityProfileStorage()->post($params, $rawBody);

        return $documentResult;
    }

    /**
     * Tries to PUT (replace) an activityState.
     *
     * @return
     */
    public function activityProfilePut()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getRawPayload();

        $params->set('headers', $request->getHeaders());

        $documentResult = $this->getStorage()->getActivityProfileStorage()->put($params, $rawBody);

        return $documentResult;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return self Nothing.
     */
    public function activityProfileDelete()
    {
        $request = $this->getContainer()->get('parser')->getData();
        $params = new Collection($request->getParameters());

        $deletionResult = $this->getStorage()->getActivityProfileStorage()->delete($params);

        return $deletionResult;
    }
}
