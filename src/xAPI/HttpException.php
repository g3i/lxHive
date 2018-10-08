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

class HttpException extends \Exception
{
    private $data = null;

    /**
     * Prepares a json response exception.
     *
     * @see API/Controller::error()
     *
     * @param string            $message
     * @param int               $statusCode valid httpd status code
     * @param array|object|null $data       extra data to be included in json response
     * @param \Exception        $previous
     *
     * @throws \Exception
     */
    public function __construct($message, $statusCode = 400, $data = [], \Exception $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Get data.
     *
     * @return mixed $data
     */
    public function getData()
    {
        return $this->data;
    }
}
