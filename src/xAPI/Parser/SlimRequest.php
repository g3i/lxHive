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

use Slim\Http\Request;

/**
 * Slim request parser
 */
class SlimRequest implements ParserInterface
{
    protected $parts;

    /**
     * constructor
     * @param RequestInterface $request
     * @return void
     */
    public function __construct(RequestInterface $request)
    {
        $this->parseRequest($request);
    }

    /**
     * Parse http request
     * @param  RequestInterface $request
     * @return void
     */
    private function parseRequest($request)
    {
        if ($request->isMultipart()) {
            $this->parts = [];
            $parts = $request->parts()->all();
            foreach ($parts as $part) {
                $this->parts[] = $this->parseSingleRequest($part);
            }
        } else {
            $this->parts = [$this->parseSingleRequest($request)];
        }
    }

    /**
     * Parses request body
     * @param  RequestInterface $request
     * @return array|object of parsed request body
     */
    private function parseSingleRequest($request)
    {
        $parserResult = new ParserResult();

        $parameters = $request->get()->all();
        $parserResult->setParameters($parameters);

        $headers = $request->headers();
        $parserResult->setHeaders($headers);

        $body = $request->getBody();
        $parserResult->setRawPayload($body);

        if ($request->getMediaType() === 'application/json') {
            $body = json_decode($body, true);

            // Some clients escape the JSON twice - handle them
            if (is_string($body)) {
                $body = json_decode($body, true);
            }
        }
        $parserResult->setPayload($body);

        return $parserResult;
    }

    /**
     * Get main part of request
     * @return ParserResult an object or array, given the payload
     */
    public function getData()
    {
        return $this->parts[0];
    }

    /**
     * Get additional parts (attachments) of request.
     * @return array of ParserResult
     */
    public function getAttachments()
    {
        $parts = $this->parts;
        array_shift($parts);

        return $parts;
    }

    /**
     * Get all parts of the request.
     * @return array of ParserResult
     */
    public function getParts()
    {
        return $this->parts;
    }
}
