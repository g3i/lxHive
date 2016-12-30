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

class PsrRequest
{
    protected $parameters;

    protected $parts;

    protected $payload;

    public function __construct(RequestInterface $request)
    {
        $this->parseRequest($request);
    }

    private function parseRequest($request)
    {

        if ($this->isMultipart($request)) {
            $this->parts = $this->parseMultipartRequest($request);
        } else {
            $this->parts = [$this->parseSingleRequest($request)];
        }
    }

    private function isMultipart($request)
    {
        return (strpos($request->getMediaType(), 'multipart/') === 0);
    }

    private function parseMultipartRequest($request)
    {
        if (false === stripos($request->getContentType(), ';')) {
            throw new \LogicException('Content-Type does not contain a \';\'');
        }
        
        $boundary = $request->getMediaTypeParams()['boundary'];
        
        // Split bodies by the boundary
        $bodies = explode('--' . $boundary, (string)$this->getBody());
        
        // RFC says, to ignore preamble and epilogue.
        $preamble = array_shift($bodies);
        $epilogue = array_pop($bodies);
        $requestParts = [];
        foreach ($bodies as $body) {
            $isHeader = true;
            $headers = [];
            $content = [];
            $data = explode('\n', $body);
            foreach ($data as $i => $line) {
                if (0 == $i) {
                    // Skip the first line
                    array_shift($data);
                    continue;
                }
                if ('' == trim($line)) {
                    // Header-body separator
                    $isHeader = false;
                    array_shift($data);
                    continue;
                }
                if ($isHeader) {
                    list($header, $value) = explode(':', $line);
                    if ($header) {
                        $headers[$header] = trim($value);
                    }
                    array_shift($data);
                } else {
                    $content = implode('\n', $data);
                    break;
                }
            }
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'text/plain';
            }

            $parserResult = new ParserResult();

            $parameters = $request->getQueryParams();
            $parserResult->setParameters($parameters);

            $parserResult->setHeaders($headers);

            $parserResult->setRawPayload($content);

            if (strpos($headers['Content-Type'], 'application/json') === 0) {
                $content = json_decode($content, true);

                // Some clients escape the JSON twice - handle them
                if (is_string($content)) {
                    $content = json_decode($content, true);
                }
            }
            $parserResult->setPayload($content);

            // Create request from mock
            $requestParts[] = $parserResult;
        }
        return $requestParts;
    }

    private function parseSingleRequest($request)
    {
        $parserResult = new ParserResult();

        $parameters = $request->getQueryParams();
        $parserResult->setParameters($parameters);

        $headers = $request->getHeaders();
        $parserResult->setHeaders($headers);

        $body = $request->getBody();
        $parserResult->setRawPayload($body);

        $parsedBody = $request->getParsedBody();
        $parserResult->setPayload($parsedBody);

        return $parserResult;
    }

    /**
     * Get the main part.
     *
     * @return ParserResult an object or array, given the payload
     */
    public function getData()
    {
        return $this->parts[0];
    }

    /**
     * Get the additional parts.
     *
     * @return \Traversable<ParserResult> an array of the parts
     */
    public function getAttachments()
    {
        $parts = $this->parts;
        array_shift($parts);

        return $parts;
    }

    /**
     * Get the parts of the request.
     *
     * @return \Traversable<ParserResult> an array of the parts
     */
    public function getParts()
    {
        return $this->parts;
    }
}
