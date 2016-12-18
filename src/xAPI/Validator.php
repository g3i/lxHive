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

use JsonSchema;
use API\HttpException as Exception;

abstract class Validator
{
    /**
     * @var JsonSchema\SchemaStorage a persistent SchemaStorage instance
     *                               Note that a SchemaStorage instance has an internal cache which takes care of loading and caching files.
     */
    private static $schemaStorage = null;

    protected $lastValidator = null;
    protected $lastSchema = null;

    protected $container;

    /**
     * Constructor, creates and caches a  instance.
     */
    public function __construct($container)
    {
        $this->container = $container;

        if (!self::$schemaStorage) {
            self::$schemaStorage = new JsonSchema\SchemaStorage();
        }
    }

    /**
     * Validate data with JsonSchema
     * We intentionally create a new Validator instance on each call.
     *
     * @param object|array $data
     * @param string       $uri   (with fragment)
     * @param bool         $debug
     *
     * @return JsonSchema\Validator
     */
    public function validateSchema($data, $uri, $debug = false)
    {
        $schema = self::$schemaStorage->getSchema($uri);
        $validator = new JsonSchema\Validator(new JsonSchema\Constraints\Factory(self::$schemaStorage, null, Constraint::CHECK_MODE_TYPE_CAST));
        $validator->check($data, $schema);

        if ($debug) {
            return $this->debugSchema($data, $uri, $validator, $schema);
        }

        return $validator;
    }

    /**
     * Debug data, validated with JsonSchema.
     *
     * @param object|array         $data
     * @param string               $uri       (with fragment)
     * @param JsonSchema\Validator $validator
     * @param object               $schema
     *
     * @throws HttpException
     */
    public function debugSchema($data, $uri, $validator, $schema)
    {
        $debug = new \StdClass();
        $debug->hasErrors = count($validator->getErrors());
        $debug->errors = ($data) ? $validator->getErrors() : [];
        $debug->uri = $uri;
        $debug->schema = $schema;
        $debug->data = $data;

        throw new Exception('DEBUG: ', (($validator->isValid()) ? 200 : 400), $debug);
    }

    /**
     * Performs general validation of the request.
     *
     * @param \Silex\Request $request The request
     */
    public function validateRequest($request)
    {
        if ($request->headers('X-Experience-API-Version') === null) {
            throw new Exception('X-Experience-API-Version header missing.', Resource::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Throw errors.
     *
     * @param string $message
     * @param mixed  $errors
     *
     * @throws HttpException
     */
    protected function throwErrors($message, $errors)
    {
        $errors = (array) $errors;
        throw new Exception($message, Resource::STATUS_BAD_REQUEST, $errors);
    }

    /**
     * Processes and Rendes validator errors in an array.
     *
     * @param string               $message
     * @param JsonSchema\Validator $validator validator instance, note that you must have validated at this stage
     *
     * @throws HttpException
     */
    protected function throwSchemaErrors($message, $validator)
    {
        $errors = $validator->getErrors();
        foreach ($errors as $key => $error) {
            if ($error['property']) {
                $errors[$key] = sprintf('[%s]: %s', $error['property'], $error['message']);
            } else {
                $errors[$key] = sprintf($error['message']);
            }
        }
        throw new Exception($message, Resource::STATUS_BAD_REQUEST, $errors);
    }

    /**
     * Gets the value of container.
     *
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets the value of container.
     *
     * @param mixed $container the container
     *
     * @return self
     */
    protected function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }
}
