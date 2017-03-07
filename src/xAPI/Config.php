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
            throw new \Exception('Config cannot be reinitiated!');
        }
    }

    /**
     * Get collection item
     * @param  string $key The key to get
     * @return mixed The value at this key
     */
    public static function get($key)
    {
        if (!self::$instantiated) {
            throw new \Exception('You must call the Config factory before being able to get and set values!');
        }
        return self::$collection->get($key);
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public static function set($key, $value)
    {
        if (!self::$instantiated) {
            throw new \Exception('You must call the Config factory before being able to get and set values!');
        }

        if (self::$collection->has($key)) {
            throw new \InvalidArgumentException('Cannot override existing Config property!');
        }
        self::$collection->set($key, $value);
    }
}
