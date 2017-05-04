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

namespace API;

abstract class Document implements DocumentInterface
{
    protected $data;

    protected $state;

    protected $version;

    /**
     * @inheritDoc
     */
    public function __construct($data = [], $documentState = DocumentState::TRUSTED, $version = null)
    {
        $this->data = $data;
        $this->state = $documentState;
        $this->version = $version;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Sets the value of xAPI data.
     *
     * @param array $data the data
     *
     * @return self
     */
    protected function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets the value of state.
     *
     * @param string $state i/o state of the document
     *
     * @return self
     */
     //TODO remove or protected
    protected function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Sets the value of version.
     *
     * @param mixed $version the version
     *
     * @return self
     */
     //TODO remove or protected
    protected function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the value of a specfier xAPI data property
     *
     * @param string $key
     * @return mixed property value
     */
    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
    }

    /**
     * Get the value of a specfier xAPI data property
     *
     * @param string $key
     * @return mixed property value
     */
    //TODO remove or protected
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Handle getters and setters
     * @param  string $name
     * @param  array $arguments
     * @return mixed
     */
     //TODO remove
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

        throw new \Exception('Document has no method "' . $name . '"');
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->getData();
    }

    /**
     * Get stored xAPI data as array
     *
     * @return array
     */
    //TODO remove
    public function toArray()
    {
        return $this->getData();
    }
}
