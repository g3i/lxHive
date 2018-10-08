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
 *
 * This file was adapted from slim.
 * License information is available at https://github.com/slimphp/Slim/blob/3.x/LICENSE.md
 *
 */

namespace API;

use API\Util\Collection;
use InvalidArgumentException;

class Config
{
    private static $collection = null;

    /**
     * Initiate the Config (only callable once)
     *
     * - A Config collection can only be factored once
     *
     * @param  array  $data The data to initiate it with
     *
     * @return void
     * @throws AppInitException if already initiated
     * @throws InvalidArgumentException if $data is not an array (object instances would be mutable)
     */
    public static function factory($data = [])
    {
        if (self::$collection) {
            throw new AppInitException('Config: Cannot be reinitiated');
        }
        if (!is_array($data)) {//PHP 5 & 7
            throw new InvalidArgumentException('Config: $data must be an array');
        }
        self::$collection = new Collection($data);
    }

    /**
     * Merge collection of items
     *
     * - New config items can be added freely, @see sConfig::set()
     *
     * @param array $data collection (assoziative array) of items
     *
     * @return void
     * @throws AppInitException if one of the keys alredy exists
     * @throws InvalidArgumentException if $data is not an array (object instances would be mutable)
     */
    public static function merge($data = [])
    {
        if (!self::$collection) {
            throw new AppInitException('Config: You must call the factory before being able to get and set values!');
        }
        if (!is_array($data)) {//PHP 5 & 7
            throw new InvalidArgumentException('Config: $data must be an array');
        }
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Get collection item
     *
     * @param string $key|array The key(s) to get
     * @param mixed $default optional return value
     *
     * @return mixed The value at this key
     * @throws AppInitException
     */
    public static function get($key, $default = null)
    {
        if (!self::$collection) {
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
        if (!self::$collection) {
            throw new AppInitException('Config: You must call the factory before being able to get and set values!');
        }
        return self::$collection->all();
    }

    /**
     * Set collection item
     *
     * - New config items can be added freely, @see sConfig::set()
     *
     * @param array $data collection (assoziative array) of items
     *
     * @return void
     * @throws AppInitException
     * @throws InvalidArgumentException if key alredy exists
     */
    public static function set($key, $value)
    {
        if (!self::$collection) {
            throw new AppInitException('Config: You must call the factory before being able to get and set values!');
        }
        if (self::$collection->has($key)) {
            throw new InvalidArgumentException('Config: Cannot override existing Config property!');
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
            return;
        }

        throw new AppInitException('Config: reset not allowed outside Bootstrap class');
    }
}
