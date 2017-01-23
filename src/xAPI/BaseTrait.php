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

namespace API;

trait BaseTrait
{
    /**
     * @var \Slim\Container
     */
    private $diContainer;

    /**
     * @var \Storage\AdapterInterface
     */
    private $storage;

    /**
     * @var \Monolog\Monolog
     */
    private $log;

    /**
     * Sets the value of diContainer.
     *
     * @param \Slim\Container $diContainer the di container
     *
     * @return self
     */
    public function setDiContainer(\Slim\Container $diContainer)
    {
        $this->diContainer = $diContainer;
        $this->storage = $diContainer['storage'];
        $this->log = $diContainer['logger'];

        return $this;
    }

    /**
     * Gets the value of diContainer.
     *
     * @return \Slim\Container
     */
    public function getDiContainer()
    {
        return $this->diContainer;
    }

        /**
     * Gets the value of diContainer.
     *
     * @return \Slim\Container
     */
    public function getContainer()
    {
        return $this->diContainer;
    }

    /**
     * Gets the value of storage.
     *
     * @return \Storage\AdapterInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Gets the value of log.
     *
     * @return \Monolog\Monolog
     */
    public function getLog()
    {
        return $this->log;
    }
}
