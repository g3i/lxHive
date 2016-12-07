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
use Rhumsaa\Uuid\Uuid;

abstract class Resource
{
    const STATUS_OK                         = 200;
    const STATUS_CREATED                    = 201;
    const STATUS_ACCEPTED                   = 202;
    const STATUS_NO_CONTENT                 = 204;

    const STATUS_MULTIPLE_CHOICES           = 300;
    const STATUS_MOVED_PERMANENTLY          = 301;
    const STATUS_FOUND                      = 302;
    const STATUS_NOT_MODIFIED               = 304;
    const STATUS_USE_PROXY                  = 305;
    const STATUS_TEMPORARY_REDIRECT         = 307;

    const STATUS_BAD_REQUEST                = 400;
    const STATUS_UNAUTHORIZED               = 401;
    const STATUS_FORBIDDEN                  = 403;
    const STATUS_NOT_FOUND                  = 404;
    const STATUS_NOT_FOUND_MESSAGE          = 'Cannot find requested resource.';
    const STATUS_METHOD_NOT_ALLOWED         = 405;
    const STATUS_METHOD_NOT_ALLOWED_MESSAGE = 'Method %s is not allowed on this resource.';
    const STATUS_NOT_ACCEPTED               = 406;
    const STATUS_CONFLICT                   = 409;
    const STATUS_PRECONDITION_FAILED        = 412;

    const STATUS_INTERNAL_SERVER_ERROR      = 500;
    const STATUS_NOT_IMPLEMENTED            = 501;

    /**
     * @var \Slim\Slim
     */
    private $slim;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->setSlim(Slim::getInstance());

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
     * @param mixed $data additional data
     * @param mixed $data exception \Exception::stackTrace() array
     */
    public static function error($code, $message = '', $data = null, $trace = null)
    {
        $slim = \Slim\Slim::getInstance();
        $message = (string) $message;
        $mode = $slim->getMode();
        $debug = $slim->config('_debug');

        // with the current implementation exceptions are not logged, we do this here and wait for Slim 3' Errorhandler class
        $error =  [
            'code' => $code,
            'details' => $data
        ];

        if($mode == 'development' && $debug){
            $error['trace'] = $trace;
        }

        // @see app debug mode
        switch (true) {
            case $code >= 500:
                $slim->log->critical($message.', '.json_encode($error));
            break;
            case $code >= 400:
                $slim->log->warning($message.', '.json_encode($error));
            break;
            default:
                $slim->log->info($message);
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
    public static function response($status = 200, $data = null, $allow = [])
    {
        /*
         * @var \Slim\Slim
         */
        $slim = \Slim\Slim::getInstance();

        $slim->status($status);
        $slim->response->headers->set('Access-Control-Allow-Origin', '*');
        $slim->response->headers->set('Access-Control-Allow-Methods', 'POST,PUT,GET,OPTIONS,DELETE');
        $slim->response->headers->set('Access-Control-Allow-Headers', 'Origin,Content-Type,Authorization,Accept,X-Experience-API-Version,If-Match,If-None-Match');
        $slim->response->headers->set('Access-Control-Allow-Credentials-Control-Allow-Origin', 'true');
        $slim->response->headers->set('Access-Control-Expose-Headers', 'ETag,Last-Modified,Content-Length,X-Experience-API-Version,X-Experience-API-Consistent-Through');
        $slim->response->headers->set('X-Experience-API-Version', $slim->config('xAPI')['latest_version']);

        $date = \API\Util\Date::dateTimeToISO8601(\API\Util\Date::dateTimeExact());
        $slim->response->headers->set('X-Experience-API-Consistent-Through', $date);

        if (!empty($allow)) {
            $slim->response()->header('Allow', strtoupper(implode(',', $allow)));
        }

        $slim->response()->setBody($data);

        return false;
    }

    public static function jsonResponse($status = 200, $data = [], $allow = [])
    {
        $slim = \Slim\Slim::getInstance();
        $slim->response->headers->set('Content-Type', 'application/json');
        $data = json_encode($data);
        self::response($status, $data, $allow);
    }

    public static function multipartResponse($status = 200, $parts = [], $allow = [])
    {
        $slim = \Slim\Slim::getInstance();
        $boundary = Uuid::uuid4()->toString();
        $slim->headers->set('Content-Type', "multipart/mixed; boundary=\"{$boundary}\"");
        $slim->headers->set('Transfer-Encoding', 'chunked');

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

        self::response($status, $content, $allow);
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
    public function getSlim()
    {
        return $this->slim;
    }

    /**
     * @param \Slim\Slim $slim
     */
    public function setSlim($slim)
    {
        $this->slim = $slim;
    }

    /**
     * @return \Sokil\Mongo\Client
     */
    public function getDocumentManager()
    {
        return $this->slim->mongo;
    }
}
