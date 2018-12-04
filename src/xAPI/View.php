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
 */

namespace API;

use API\Util\Collection;

abstract class View extends Collection
{
    use BaseTrait;

    protected $items;

    protected $response;

    /**
     * @constructor
     */
    public function __construct($response, $container, $items = [])
    {
        parent::__construct($items);
        $this->setResponse($response);
        $this->setContainer($container);
        $this->items = $items;
    }

    /**
     * Gets items.
     *
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Gets  response.
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the items.
     *
     * @param mixed $items the items
     *
     * @return self
     */
    protected function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Sets the response.
     *
     * @param mixed $response the response
     *
     * @return self
     */
    protected function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }
}
