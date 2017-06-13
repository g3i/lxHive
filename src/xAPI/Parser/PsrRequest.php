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

/**
 * HTTP request parser
 */
class PsrRequest
{
    protected $parameters;

    protected $parts;

    protected $payload;

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
    public function parseRequest($request)
    {
        if ($this->isMultipart($request)) {
            $this->parts = $this->parseMultipartRequest($request);
        } else {
            $this->parts = [$this->parseSingleRequest($request)];
        }
    }

    /**
     * Checks if a request is a multipart request
     * @param  RequestInterface $request
     * @return boolean
     */
    private function isMultipart($request)
    {
        return (strpos($request->getMediaType(), 'multipart/') === 0);
    }

    /**
     * Strips and parses multipart request body
     * @param  RequestInterface $request
     * @return array of parsed request body parts
     */
    private function parseMultipartRequest($request)
    {
        if (false === stripos($request->getContentType(), ';')) {
            throw new \LogicException('Content-Type does not contain a \';\'');
        }

        $boundary = $request->getMediaTypeParams()['boundary'];

        // Split bodies by the boundary
        $bodies = explode('--' . $boundary, (string)$request->getBody());

        // RFC says, to ignore preamble and epilogue.
        $preamble = array_shift($bodies);
        $epilogue = array_pop($bodies);
        $requestParts = [];
        foreach ($bodies as $body) {
            $isHeader = true;
            $headers = [];
            $content = [];
            $data = explode(PHP_EOL, $body);
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
                        $headers[strtolower($header)] = explode(',', trim($value));
                    }
                    array_shift($data);
                } else {
                    $content = implode(PHP_EOL, $data);
                    break;
                }
            }

            if (!isset($headers['content-type'])) {
                $headers['content-type'] = ['text/plain'];
            }

            $parserResult = new ParserResult();

            $parameters = $request->getQueryParams();
            $parserResult->setParameters($parameters);

            $requestHeaders = $request->getHeaders();
            $parsedHeaders = [];
            // TODO: I hate this, there must be a better way!
            foreach ($requestHeaders as $key => $value) {
                $key = strtr(strtolower($key), '_', '-');
                if (strpos($key, 'http-') === 0) {
                    $key = substr($key, 5);
                }
                $parsedHeaders[$key] = $value;
            }
            $parserResult->setHeaders($headers + $parsedHeaders);

            $parserResult->setRawPayload($content);

            if (strpos($headers['content-type'][0], 'application/json') === 0) {
                $content = json_decode($content);

                // Some clients escape the JSON twice - handle them
                if (is_string($content)) {
                    $content = json_decode($content);
                }
            }
            $parserResult->setPayload($content);

            // Create request from mock
            $requestParts[] = $parserResult;
        }
        return $requestParts;
    }

    /**
     * Parses request body
     * @param  RequestInterface $request
     * @return array|object of parsed request body
     */
    private function parseSingleRequest($request)
    {
        $parserResult = new ParserResult();
        $parameters = $request->getQueryParams();
        // CORS override!
        if (isset($parameters['method'])) {
            mb_parse_str($request->getUri()->getQuery(), $parameters);
        }
        $parserResult->setParameters($parameters);

        $headers = $request->getHeaders();
        $parsedHeaders = [];
        // TODO: I hate this, there must be a better way!
        foreach ($headers as $key => $value) {
            $key = strtr(strtolower($key), '_', '-');
            if (strpos($key, 'http-') === 0) {
                $key = substr($key, 5);
            }
            $parsedHeaders[$key] = $value;
        }
        $parserResult->setHeaders($parsedHeaders);

        $body = $request->getBody();
        $parserResult->setRawPayload($body);

        $parsedBody = $request->getParsedBody();
        $parserResult->setPayload($parsedBody);

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
