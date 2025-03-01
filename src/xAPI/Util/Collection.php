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

namespace API\Util;

use ArrayIterator;

class Collection implements CollectionInterface
{
    /**
     * The source data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create new collection
     *
     * @param array|object $items Pre-populate collection with this key-value array
     */
    public function __construct($items = [])
    {
        $this->replace((array) $items);
    }

    /********************************************************************************
     * Collection interface
     *******************************************************************************/
    private function getArray($keys, $default)
    {
        $array = $this->data;

        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }

    private function hasArray($keys)
    {
        $array = $this->data;

        foreach ($keys as $key) {
            if (isset($array[$key])) {
                // Loop onwards
                $array = $array[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get collection item for key
     *
     * @param string $key     The data key
     * @param mixed  $default The default value to return if data key does not exist
     *
     * @return mixed The key's value, or the default value
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getArray($key, $default);
        } else {
            return $this->has($key) ? $this->data[$key] : $default;
        }
    }

    /**
     * Add item to collection, replacing existing items with the same data key
     *
     * @param array $items Key-value array of data to append to this collection
     */
    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get collection keys
     *
     * @return array The collection's source data keys
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Does this collection have a given key?
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function has($key)
    {
        if (is_array($key)) {
            return $this->hasArray($key);
        } else {
            return array_key_exists($key, $this->data);
        }
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Remove all items from collection
     */
    public function clear()
    {
        $this->data = [];
    }

    /********************************************************************************
     * Property Overloading
     *******************************************************************************/

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __isset($key)
    {
        return $this->has($key);
    }

    public function __unset($key)
    {
        $this->remove($key);
    }

    /********************************************************************************
     * Method Overloading
     *******************************************************************************/

    /**
     * Handle getters and setters
     * @param  [type] $name      [description]
     * @param  [type] $arguments [description]
     * @return [type]            [description]
     */
    public function __call($name, $arguments)
    {
        // Getter
        if ('get' === strtolower(substr($name, 0, 3))) {
            return $this->get(lcfirst(substr($name, 3)));
        }

        // Setter
        if ('set' === strtolower(substr($name, 0, 3)) && isset($arguments[0])) {
            return $this->set(lcfirst(substr($name, 3)), $arguments[0]);
        }
    }

    /********************************************************************************
     * JsonSerializable interface
     *******************************************************************************/

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }

    /********************************************************************************
     * Arrayable interface
     *******************************************************************************/

    public function toArray()
    {
        return $this->data;
    }

    /********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * Get number of items in collection
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->data);
    }

    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/

    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
}
