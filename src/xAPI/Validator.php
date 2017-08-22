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

namespace API;

use API\Config;
use JsonSchema;
use API\HttpException as Exception;
use API\BaseTrait;

abstract class Validator
{
    use BaseTrait;

    /**
     * @var JsonSchema\SchemaStorage a persistent SchemaStorage instance
     * Note that a SchemaStorage instance has an internal cache which takes care of loading and caching files.
     */
    private static $schemaStorage = null;

    protected $lastValidator = null;
    protected $lastSchema = null;

    protected $debug;
    private $debugData = null;

    /**
     * Constructor, creates and caches a  instance.
     */
    public function __construct($container)
    {
        $this->setContainer($container);

        if (!self::$schemaStorage) {
            self::$schemaStorage = new JsonSchema\SchemaStorage();
        }
        $this->debug = Config::get('debug', false);
    }

    /**
     * Validate data with JsonSchema
     * We intentionally create a new Validator instance on each call.
     *
     * @param object|array $data
     * @param string       $uri   (with fragment)
     *
     * @return JsonSchema\Validator
     */
    public function validateSchema($data, $uri)
    {
        $schema = self::$schemaStorage->getSchema($uri);
        $validator = new JsonSchema\Validator(new JsonSchema\Constraints\Factory(self::$schemaStorage, null, JsonSchema\Constraints\Constraint::CHECK_MODE_TYPE_CAST));
        $validator->check($data, $schema);

        if ($this->debug) {
            $this->debugData = $this->debugSchema($data, $uri, $validator, $schema);
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
     * @return void
     * @throws HttpException
     */
    public function debugSchema($data, $uri, $validator, $schema)
    {
        return [
            'hasErrors' => count($validator->getErrors()),
            'errors' => ($data) ? $validator->getErrors() : [],
            'uri' => $uri,
            'schema' => $schema,
            'data' => $data,
        ];
    }

    /**
     * Performs general validation of the request.
     *
     * @return void
     * @throws Exception
     */
    public function validateRequest()
    {
        $version = $this->getContainer()->get('version');//run version container
    }

    /**
     * Throw errors.
     *
     * @param string $message
     * @param mixed  $errors
     *
     * @return void
     * @throws HttpException
     */
    protected function throwErrors($message, $errors)
    {
        $errors = (array) $errors;
        throw new Exception($message, Controller::STATUS_BAD_REQUEST, $errors);
    }

    /**
     * Processes and Rendes validator errors in an array.
     *
     * @param string               $message
     * @param JsonSchema\Validator $validator validator instance, note that you must have validated at this stage
     *
     * @return void
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
        if ($this->debug) {
            $errors['debug'] = $this->debugData;
        }
        throw new Exception($message, Controller::STATUS_BAD_REQUEST, $errors);
    }
}
