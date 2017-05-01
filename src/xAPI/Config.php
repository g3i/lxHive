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
 *
 * This file was adapted from slim.
 * License information is available at https://github.com/slimphp/Slim/blob/3.x/LICENSE.md
 *
 */

namespace API;

use API\Util\Collection;

class Config
{
    private static $collection = null;
    private static $instantiated = false;

    /**
     * Initiate the Config (only callable once)
     * @param  Collection|array  $data The data to initiate it with
     * @return void
     * @throws AppInitException
     */
    public static function factory($data = [])
    {
        if (null === self::$collection && !self::$instantiated) {
            if ($data instanceof Collection) {
                self::$collection = $data;
            } else {
                // Data should be an array if it's not a Collection object
                self::$collection = new Collection($data);
            }
            self::$instantiated = true;
        } else {
            throw new AppInitException('Config: Cannot be reinitiated');
        }
    }

    /**
     * Merege collection of items
     *
     * @param array $data collection (assoziative array) of items
     * @throws \Exception if key alredy exists
     * @see sConfig::set()
     */
    public static function merge($data = [])
    {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Get collection item
     * @param  string $key The key to get
     * @return mixed The value at this key
     * @throws AppInitException
     */
    public static function get($key, $default = null)
    {
        if (!self::$instantiated) {
            throw new AppInitException('Config: You must call the factory before being able to get and set values!');
        }
        return self::$collection->get($key, $default);
    }

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     * @throws AppInitException
     */
    public static function all()
    {
        if (!self::$instantiated) {
            throw new AppInitException('Config: You must call the factory before being able to get and set values!');
        }
        return self::$collection->all();
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     * @throws AppInitException
     * @throws InvalidArgumentException
     */
    public static function set($key, $value)
    {
        if (!self::$instantiated) {
            throw new AppInitException('Config: You must call the Config factory before being able to get and set values!');
        }

        if (self::$collection->has($key)) {
            throw new \InvalidArgumentException('Config: Cannot override existing Config property!');
        }
        self::$collection->set($key, $value);
    }

    /**
     * Reset configuration (unit tests)
     * @ignore do not compile to docs
     * @return void
     * @throws AppInitException if called from outside Bootstrap
     */
    public static function reset()
    {
        $trace = debug_backtrace();
        $class = $trace[1]['class'];

        if ($class === 'API\\Bootstrap') {
            self::$collection = null;
            self::$instantiated = false;
            return;
        }

        throw new AppInitException('Config: reset not allowed outside Bootstrap class');
    }
}
