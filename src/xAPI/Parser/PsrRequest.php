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

namespace API\Parser;

use Psr\Http\Message\RequestInterface;

class PsrRequest implements ParserInterface
{
    // TODO: This class is for PSR-7 requests, this is being prepared for Slim3 switch!
    protected $parameters;

    protected $parts;

    protected $payload;

    public function __construct(RequestInterface $request)
    {
        $this->parseRequest($request);
    }

    private function parseRequest()
    {
    }

    /**
     * Get the main part.
     *
     * @return ParserResult an object or array, given the payload
     */
    public function getData()
    {
    }

    /**
     * Get the additional parts.
     *
     * @return \Traversable<ParserResult> an array of the parts
     */
    public function getAttachments()
    {
    }

    /**
     * Get the parts of the request.
     *
     * @return \Traversable<ParserResult> an array of the parts
     */
    public function getParts()
    {
    }
}
