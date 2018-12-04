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

namespace API\Parser;

/**
 * Storage class for Request parser
 */
class ParserResult
{
    /**
     * @var array request params
     */
    public $parameters;
    /**
     * @var array request headers
     */
    public $headers;

    /**
     * @var string request payload (JSON)
     */
    public $rawPayload;

    /**
     * @var object|array parsed json data
     */
    public $payload;

    /**
     * Get request parameters
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set (parsed) request parameters.
     * @param  array $parameters
     * @return self
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get headers.
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets (parsed) headers
     * @param array $headers the headers
     * @return self
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Gets rawPayload.
     * @return string
     */
    public function getRawPayload()
    {
        return $this->rawPayload;
    }

    /**
     * Sets rawPayload.
     * @param string
     * @return self
     */
    public function setRawPayload($rawPayload)
    {
        $this->rawPayload = $rawPayload;

        return $this;
    }

    /**
     * Gets (parsed) )payload.
     * @return array|object
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Sets (parsed) payload.
     * @param array|object $payload (json_decode)
     * @return self
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }
}
