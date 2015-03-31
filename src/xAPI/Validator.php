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

namespace API;

use JsonSchema;
use API\Validator\Exception;

abstract class Validator
{
    /**
     * @var \JsonSchema\Validator
     */
    private $schemaValidator;

    /**
     * @var \JsonSchema\Uri\UriRetriever
     */
    private $retriever;

    /**
     * @var \JsonSchema\RefResolver
     */
    private $refResolver;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setDefaultSchemaValidator();
    }

    /**
     * @return \JsonSchema\Validator
     */
    public function getSchemaValidator()
    {
        return $this->schemaValidator;
    }
    /**
     * @param \JsonSchema\Validator $schemaValidator
     */
    public function setSchemaValidator($schemaValidator)
    {
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * @return \JsonSchema\RefResolver
     */
    public function getSchemaReferenceResolver()
    {
        return $this->refResolver;
    }
    /**
     * @param \JsonSchema\RefResolver $refResolver
     */
    public function setSchemaReferenceResolver($refResolver)
    {
        $this->refResolver = $refResolver;
    }

    /**
     * @return \JsonSchema\Uri\UriRetriever
     */
    public function getSchemaRetriever()
    {
        return $this->retriever;
    }
    /**
     * @param \JsonSchema\Uri\UriRetriever $uriRetriever
     */
    public function setSchemaRetriever($uriRetriever)
    {
        $this->retriever = $uriRetriever;
    }

    /**
     * Sets the default schema validator.
     */
    public function setDefaultSchemaValidator()
    {
        $this->retriever = new JsonSchema\Uri\UriRetriever();
        $this->refResolver = new JsonSchema\RefResolver($this->retriever);
        $this->schemaValidator = new JsonSchema\Validator();
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
}
