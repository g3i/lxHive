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

use Slim\Slim;
use Ramsey\Uuid\Uuid;

abstract class Resource
{
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_ACCEPTED = 202;
    const STATUS_NO_CONTENT = 204;

    const STATUS_MULTIPLE_CHOICES = 300;
    const STATUS_MOVED_PERMANENTLY = 301;
    const STATUS_FOUND = 302;
    const STATUS_NOT_MODIFIED = 304;
    const STATUS_USE_PROXY = 305;
    const STATUS_TEMPORARY_REDIRECT = 307;

    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_NOT_FOUND_MESSAGE = 'Cannot find requested resource.';
    const STATUS_METHOD_NOT_ALLOWED = 405;
    const STATUS_METHOD_NOT_ALLOWED_MESSAGE = 'Method %s is not allowed on this resource.';
    const STATUS_NOT_ACCEPTED = 406;
    const STATUS_CONFLICT = 409;
    const STATUS_PRECONDITION_FAILED = 412;

    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_NOT_IMPLEMENTED = 501;

    /**
     * @var \Slim\Slim
     */
    private $slim;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->slim = Slim::getInstance();

        $this->init();
    }

    /**
     * Default init, use for overwrite only.
     */
    public function init()
    {
    }

    /**
     * Default get method.
     */
    public function get()
    {
        $this->error(self::STATUS_METHOD_NOT_ALLOWED, sprintf(self::STATUS_METHOD_NOT_ALLOWED_MESSAGE, 'GET'));
    }

    /**
     * Default post method.
     */
    public function post()
    {
        $this->error(self::STATUS_METHOD_NOT_ALLOWED, sprintf(self::STATUS_METHOD_NOT_ALLOWED_MESSAGE, 'POST'));
    }

    /**
     * Default put method.
     */
    public function put()
    {
        $this->error(self::STATUS_METHOD_NOT_ALLOWED, sprintf(self::STATUS_METHOD_NOT_ALLOWED_MESSAGE, 'PUT'));
    }

    /**
     * Default delete method.
     */
    public function delete()
    {
        $this->error(self::STATUS_METHOD_NOT_ALLOWED, sprintf(self::STATUS_METHOD_NOT_ALLOWED_MESSAGE, 'DELETE'));
    }

    /**
     * General options method.
     */
    public function options()
    {
        $this->error(self::STATUS_METHOD_NOT_ALLOWED, sprintf(self::STATUS_METHOD_NOT_ALLOWED_MESSAGE, 'OPTIONS'));
    }

    /**
     * Error handler.
     *
     * @param int    $code    Error code
     * @param string $message Error message
     * @param mixed  $data    additional data
     * @param mixed  $data    exception \Exception::stackTrace() array
     */
    public static function error($container, $code, $message = '', $data = null, $trace = null)
    {
        $message = (string) $message;

        $error = [
            'code' => $code,
            'details' => $data,
        ];

        $error['trace'] = $trace;

        if ($code >= 500) {
            $container['logger']->critical($message.', '.json_encode($error));
        }
        else if ($code >= 400) {
            $container['logger']->warning($message.', '.json_encode($error));
        } else {
            $container['logger']->info($message); 
        }

        $response = [
            'error_message' => $message,
            'details' => ($code >= 500) ? $error : $data,
        ];

        self::jsonResponse($code, $response);
    }

    /**
     * @param int   $status HTTP status code
     * @param array $data   The data
     * @param array $allow  Allowed methods
     */
    public static function response($container, $status = 200, $data = null, $allow = [])
    {
        $date = \API\Util\Date::dateTimeToISO8601(\API\Util\Date::dateTimeExact());
        
        $response = $container['response'];
        $response = $response->withStatus($status)
                             ->withHeader('Access-Control-Allow-Origin', '*')
                             ->withHeader('Access-Control-Allow-Methods', 'POST,PUT,GET,OPTIONS,DELETE')
                             ->withHeader('Access-Control-Allow-Headers', 'Origin,Content-Type,Authorization,Accept,X-Experience-API-Version,If-Match,If-None-Match')
                             ->withHeader('Access-Control-Allow-Credentials-Control-Allow-Origin', 'true')
                             ->withHeader('Access-Control-Expose-Headers', 'ETag,Last-Modified,Content-Length,X-Experience-API-Version,X-Experience-API-Consistent-Through')
                             ->withHeader('X-Experience-API-Version', $slim->config('xAPI')['latest_version'])
                             ->withHeader('X-Experience-API-Consistent-Through', $date);

        if (!empty($allow)) {
            $response = $response->withHeader('Allow', strtoupper(implode(',', $allow)));
        }

        $response = $response->write($data);

        $container['response'] = $response;
    }

    public static function jsonResponse($container, $status = 200, $data = [], $allow = [])
    {
        $response = $container['response'];
        $response = $response->withHeader('Content-Type', 'application/json');
        $container['response'] = $response;
        $data = json_encode($data);
        self::response($container, $status, $data, $allow);
    }

    public static function multipartResponse($container, $status = 200, $parts = [], $allow = [])
    {
        $response = $container['response'];
        $boundary = Uuid::uuid4()->toString();
        $response = $response->withHeader('Content-Type', "multipart/mixed; boundary=\"{$boundary}\"")
                             ->withHeader('Transfer-Encoding', 'chunked');

        $content = '';
        foreach ($parts as $part) {
            $content .= "--{$boundary}\r\n";
            $content .= "{$part->headers}\r\n";
            $content .= $part->getContent();
            $content .= "\r\n";
        }
        $content .= "--{$boundary}--";
        // Finally send all the content.
        $content = strlen($content)."\r\n".$content;

        $container['response'] = $response;

        self::response($container, $status, $content, $allow);
    }

    /**
     * @param $version The xAPI version requested
     * @param $resource The main resource
     * @param $subResource An optional subresource
     *
     * @return mixed
     */
    public static function load($version, $resource, $subResource)
    {
        $versionNamespace = $version->generateClassNamespace();
        if (null !== $subResource) {
            $class = __NAMESPACE__.'\\Resource\\'.$versionNamespace.'\\'.ucfirst($resource).'\\'.ucfirst($subResource);
        } else {
            $class = __NAMESPACE__.'\\Resource\\'.$versionNamespace.'\\'.ucfirst($resource);
        }
        if (!class_exists($class)) {
            return;
        }

        return new $class();
    }

    /**
     * @return \Slim\Slim
     */
    public function getContainer()
    {
        return $this->slim;
    }

    /**
     * @return \Slim\Slim
     */
    public function getSlim()
    {
        return $this->slim;
    }

    /**
     * @return \Sokil\Mongo\Client
     */
    public function getDocumentManager()
    {
        return $this->slim->mongo;
    }
}
