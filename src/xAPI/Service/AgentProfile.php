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

class AgentProfile extends Service
{
    // Will be deprecated with AgentProfileResult class
    /**
     * Activity profiles.
     *
     * @var array
     */
    protected $agentProfiles;

    // Will be deprecated with AgentProfileResult class
    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

    // Will be deprecated with AgentProfileResult class
    /**
     * Is this a single activity state fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * Fetches agent profiles according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of agentProfile objects.
     */
    public function agentProfileGet($request)
    {
        $params = new Set($request->get());

        $cursor = $this->getStorage()->getAgentProfileStorage()->getAgentProfilesFiltered($params);

        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Tries to save (merge) an agentProfile.
     */
    public function agentProfilePost($request)
    {
        $params = new Set($request->get());

        // Validation has been completed already - everything is assumed to be valid
        $rawBody = $request->getBody();

        $params->set('headers', $request->headers());

        $agentProfileDocument = $this->getStorage()->getAgentProfileStorage()->postAgentProfile($params, $rawBody);

        $this->single = true;
        $this->agentProfiles = [$agentProfileDocument];

        return $this;
    }

    /**
     * Tries to PUT (replace) an agentProfile.
     *
     * @return
     */
    public function agentProfilePut($request)
    {
        // Validation has been completed already - everyhing is assumed to be valid (from an external view!)
        $rawBody = $request->getBody();
        $body = json_decode($rawBody, true);

        // Some clients escape the JSON - handle them
        if (is_string($body)) {
            $body = json_decode($body, true);
        }

        // Single
        $params = new Set($request->get());

        $params->set('headers', $request->headers());

        $agentProfileDocument = $this->getStorage()->getAgentProfileStorage()->putAgentProfile($params, $rawBody);
        
        $this->single = true;
        $this->agentProfiles = [$agentProfileDocument];

        return $this;
    }

    /**
     * Fetches activity states according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return self Nothing.
     */
    public function agentProfileDelete($request)
    {
        $params = new Set($request->get());

        $params->set('headers', $request->headers());

        $this->getStorage()->getAgentProfileStorage()->deleteAgentProfile($params);

        return $this;
    }

    /**
     * Trims quotes from the header.
     *
     * @param string $headerString Header
     *
     * @return string Trimmed header
     */
    private function trimHeader($headerString)
    {
        return trim($headerString, '"');
    }

    /**
     * Gets the Agent profiles.
     *
     * @return array
     */
    public function getAgentProfiles()
    {
        return $this->agentProfiles;
    }

    /**
     * Sets the Agent profiles.
     *
     * @param array $agentProfiles the agent profiles
     *
     * @return self
     */
    public function setAgentProfiles(array $agentProfiles)
    {
        $this->agentProfiles = $agentProfiles;

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
     * Gets the Is this a single agent profile fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single agent profile fetch?.
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
