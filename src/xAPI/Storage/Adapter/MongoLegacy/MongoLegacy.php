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

namespace API\Storage\Adapter\MongoLegacy;

use API\Storage\Adapter\AdapterInterface;

class MongoLegacy implements AdapterInterface
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getStatementStorage()
    {
        $statementStorage = new Statement($this->getContainer());
        return $statementStorage;
    }

    public function getAttachmentStorage()
    {
        $attachmentStorage = new Attachment($this->getContainer());
        return $attachmentStorage;
    }

    public function getUserStorage()
    {
        $userStorage = new User($this->getContainer());
        return $userStorage;
    }

    public function getLogStorage()
    {
        $logStorage = new Log($this->getContainer());
        return $logStorage;
    }

    public function getActivityStorage()
    {
        $activityStorage = new Activity($this->getContainer());
        return $activityStorage;
    }


    public function getActivityStateStorage()
    {
        $activityStateStorage = new ActivityState($this->getContainer());
        return $activityStateStorage;
    }


    public function getActivityProfileStorage()
    {
        $activityProfileStorage = new ActivityProfile($this->getContainer());
        return $activityProfileStorage;
    }


    public function getAgentProfileStorage()
    {
        $agentProfileStorage = new AgentProfile($this->getContainer());
        return $agentProfileStorage;
    }

    /**
     * Gets the value of container.
     *
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets the value of container.
     *
     * @param mixed $container the container
     *
     * @return self
     */
    protected function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }
}