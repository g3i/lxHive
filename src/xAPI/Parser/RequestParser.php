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

use Psr\Http\Message\RequestInterface;
use API\HttpException;
use API\Controller;

/**
 * HTTP request parser, handling with both application/json and multipart/mixed requests
 * @see https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Communication.md#15-content-types
 *
 * TODO This is a Slim 2.* leftover, needs to be replaced with an extended PSR7 compliant parser and storage
 */
class RequestParser
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
            throw new HttpException('Content-Type does not contain a \';\'', Controller::STATUS_BAD_REQUEST);
        }

        if (!isset($request->getMediaTypeParams()['boundary'])) {
            throw new HttpException('No boundary present on multipart request.', Controller::STATUS_BAD_REQUEST);
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

            $params = $request->getQueryParams();
            $parserResult->setQueryParams($params);

            $parsedHeaders = $this->parseRequestHeaders($request);
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

        if (empty($requestParts)) {
            throw new HttpException('Invalid multipart request!', Controller::STATUS_BAD_REQUEST);
        }

        return $requestParts;
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
        // TODO 0.11.x: Test if array_slice works faster than this!
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


    /**
     * Parses request body
     * @param  RequestInterface $request
     * @return array|object of parsed request body
     */
    private function parseSingleRequest($request)
    {
        $parserResult = new ParserResult();
        $params = $request->getQueryParams();
        // CORS override!
        if (isset($params['method'])) {
            mb_parse_str($request->getUri()->getQuery(), $params);
        }
        $parserResult->setQueryParams($params);

        $headers = $request->getHeaders();

        $parsedHeaders = $this->parseRequestHeaders($request);
        $parserResult->setHeaders($parsedHeaders);

        $body = $request->getBody();
        $parserResult->setRawPayload($body);

        $parsedBody = $request->getParsedBody();
        $parserResult->setPayload($parsedBody);

        return $parserResult;
    }

    /**
     * Parses and transforms request Headers
     * @param  RequestInterface $request
     *
     * @return array selection of parsed request header
     */
    private function parseRequestHeaders($request)
    {
        $requestHeaders = $request->getHeaders();
        $parsedHeaders = [];

            // TODO 0.11.x cumbersome logic, improve this, do we really need to strip http- prefix?
            foreach ($requestHeaders as $key => $value) {
                $key = strtr(strtolower($key), '_', '-');
                if (strpos($key, 'http-') === 0) {
                    $key = substr($key, 5);
                }
                $parsedHeaders[$key] = $value;
            }
        return $parsedHeaders;
    }
}
