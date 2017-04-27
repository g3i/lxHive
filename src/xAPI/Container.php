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
use Pimple\Container as PimpleContainer;

/**
 * Lightweight Interop\Container\ContainerInterface compliant implementation of Symfony's Pimple container
 *
 * @see \Pimple\Container
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md
 */
class Container extends PimpleContainer implements ContainerInterface
{
    /**
     * @var bool $locked prevents overwriting of any (not just services) properties if set to true
     */
    private $locked = false;

    /**
     * @inheritdoc
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);
    }

    /**
     * Locks a container, all existing and new properties cannot be overwritten
     * @return void
     */
    public function lock()
    {
        $this->locked = true;
    }

    /**
     * Gets a parameter or an object.
     *      $this->get('doesnotexist') will throw an ContainerException exception
     *      $this->get('doesnotexist', null) will return null (or any other value) instead
     * The first one is recommended if using it as a service store, the second for a pure value store
     *
     * @param string $id The unique identifier for the parameter or object
     * @return mixed The value for $id ore return arg, if it was set
     * @throws ContainerException Thrown if no entry was found for this identifier and no return arg was set.
     * @throws \InvalidArgumentException
     */
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            // return an optional value
            if (func_num_args() > 1){
                return func_get_arg(1);
            }
            throw new ContainerException(sprintf('Property "%s" does not exist.', $id));
        }
        return $this->offsetGet($id);
    }

    /**
     * Sets a parameter or an object.
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to define an object
     *
     * @throws \RuntimeException Prevent override of a frozen service
     */
    public function set($id, $value)
    {
        $this->offsetSet($id, $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($id, $value)
    {
        if ($this->offsetExists($id) && $this->locked) {
            throw new ContainerException(sprintf('Cannot override "%s"inside a locked collection.', $id));
        }
        parent::offsetSet($id, $value);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }

}
