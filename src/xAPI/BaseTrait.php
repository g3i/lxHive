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
use Interop\Container\ContainerInterface;

trait BaseTrait
{
    /**
     * @var \Slim\Container
     */
    private $container;

    /**
     * Sets service container.
     *
     * @param ContainerInterface $container
     * @return self
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Gets service ontainer.
     *
     * @return \Slim\Container
     */
    public function getContainer()
    {
        if(!$this->container){
            throw new \Exception('Basetrait: no container was set.');
        }
        return $this->container;
    }

    /**
     * Gets storage service.
     *
     * @return \Storage\AdapterInterface
     * @throws \Exception
     * @throws \API\ContainerException
     */
    public function getStorage()
    {
        if(!$this->container){
            throw new \Exception('Basetrait: no container was set.');
        }
        return $this->container->get('storage');
    }

    /**
     * Gets log service.
     *
     * @return \Monolog\Monolog
     * @throws \Exception
     * @throws \API\ContainerException
     */
    public function getLog()
    {
        if(!$this->container){
            throw new \Exception('Basetrait: no container was set.');
        }
        return $this->container->get('log');
    }
}
