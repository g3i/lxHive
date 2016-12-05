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

namespace API\Validator\V10;

use API\Validator;
use API\Resource;
use JsonSchema;

class Statement extends Validator
{
    /**
     * @var JsonSchema\Uri\UriResolver $refResolve stores instance (utilize refResolver cache);
     */
    private $refResolver = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->refResolver = new JsonSchema\RefResolver(new JsonSchema\Uri\UriRetriever(), new JsonSchema\Uri\UriResolver());
        parent:: __construct();
    }

    // Handles the validation of GET /statements
    public function validateGetRequest($request)
    {
        $data = $request->get();

        foreach ($data as $key => $value) {
            $decodedValue = json_decode($value);
            if (json_last_error() == JSON_ERROR_NONE) {
                $data[$key] = $decodedValue;
            }
        }

        if (!empty($data)) {
            $data = (object) $data;
        }

        $this->validateSchema($data, 'getParameters');
    }

    // POST-ing a statement validation
    public function validatePostRequest($request)
    {
        // Then do specific validation
        $data = $request->getBody();
        $data = json_decode($data);

        $this->validateSchema($data, 'postBody');
    }

    // PUT-ing one or more statements validation
    public function validatePutRequest($request)
    {
        // Then do specific validation
        $data = $request->get();

        $schema = $this->validateSchema($data, 'putParameters');

        $data = $request->getBody();
        $data = json_decode($data);

        $this->validateSchema($data, 'putBody');
    }

    /**
     * validate data with JsonSchema
     *
     * @param object $data
     * @param string $fragment
     *
     * @throws Exception
     */
    protected function validateSchema($data, $fragment = '')
    {
        $fragment = ($fragment) ? '#'.$fragment : '';
        $schema = new \stdClass();
        $schema = $this->refResolver->resolve('file://'.__DIR__.'/Schema/Statements.json'.$fragment);

        // Validate
        $validator = new JsonSchema\Validator();
        $validator->check($data, $schema);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            foreach ($errors as $key => $error) {
                if($error['property']){
                    $errors[$key] = sprintf("[%s]: %s", $error['property'], $error['message']);
                }else{
                    $errors[$key] = sprintf($error['message']);
                }

            }
            $this->throwErrors($errors, $fragment);
        }
    }

    /**
     * throw validatior errors
     *
     * @param array $errors
     * @param string $validator
     *
     * @throws Exception
     */
    protected function throwErrors(array $errors, $validator = 'Statement')
    {
        $msg = '{"validator":"'.$validator. '", "errors": '.json_encode($errors).'}';
        throw new \Exception($msg, Resource::STATUS_BAD_REQUEST);
    }
}
