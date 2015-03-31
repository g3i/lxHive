<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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
use API\Validator\Exception;

class Statement extends Validator
{
    protected function retrieveByFragment($fragment)
    {
        $schema = $this->getSchemaRetriever()->retrieve('file://'.__DIR__.'/Schema/Statements.json#'.$fragment);

        return $schema;
    }

    protected function throwErrors($message, $errors)
    {
        $message .= ' Violations: ';
        foreach ($errors as $error) {
            $message .= sprintf("[%s] %s\n", $error['property'], $error['message']);
        }
        throw new Exception($message, Resource::STATUS_BAD_REQUEST);
    }

    // Handles the validation of GET /statements
    public function validateGetRequest($request)
    {
        $data = $request->get();

        $schema = $this->retrieveByFragment('getParameters');
        $this->getSchemaReferenceResolver()->resolve($schema);
        $this->getSchemaValidator()->check($data, $schema);

        if (!$this->getSchemaValidator()->isValid()) {
            $this->throwErrors('GET parameters do not validate.', $this->getSchemaValidator()->getErrors());
        }
    }

    // POST-ing a statement validation
    public function validatePostRequest($request)
    {
        // Then do specific validation
        $data = $request->getBody();
        $data = json_decode($data);

        $schema = $this->retrieveByFragment('postBody');
        $this->getSchemaReferenceResolver()->resolve($schema);
        $this->getSchemaValidator()->check($data, $schema);

        if (!$this->getSchemaValidator()->isValid()) {
            $this->throwErrors('Statements do not validate.', $this->getSchemaValidator()->getErrors());
        }
    }

    // PUT-ing one or more statements validation
    public function validatePutRequest($request)
    {
        // Then do specific validation
        $data = $request->get();
        $schema = $this->retrieveByFragment('putParameters');
        $this->getSchemaReferenceResolver()->resolve($schema);
        $this->getSchemaValidator()->check($data, $schema);

        if (!$this->getSchemaValidator()->isValid()) {
            $this->throwErrors('PUT parameters do not validate.', $this->getSchemaValidator()->getErrors());
        }

        $data = $request->getBody();
        $data = json_decode($data);

        $schema = $this->retrieveByFragment('putBody');
        $this->getSchemaReferenceResolver()->resolve($schema);
        $this->getSchemaValidator()->check($data, $schema);

        if (!$this->getSchemaValidator()->isValid()) {
            $this->throwErrors('Statements do not validate.', $this->getSchemaValidator()->getErrors());
        }
    }
}
