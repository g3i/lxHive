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

namespace API\Resource\V10;

use API\Resource;
use API\Service\Statement as StatementService;
use API\Validator\V10\Statement as StatementValidator;
use API\View\V10\Statements as StatementView;

class Statements extends Resource
{
    /**
     * @var \API\Service\Statement
     */
    private $statementService;

    /**
     * @var \API\Validator\Statement
     */
    private $statementValidator;

    /**
     * Get statement service.
     */
    public function init()
    {
        $this->setStatementService(new StatementService($this->getSlim()));
        $this->setStatementValidator(new StatementValidator());
    }

    /**
     * Handle the Statement GET request.
     */
    public function get()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('statements/read');

        // Do the validation
        $this->statementValidator->validateRequest($request);
        $this->statementValidator->validateGetRequest($request);

        // Load the statements - this needs to change, drastically, as it's garbage
        $this->statementService->statementGet($request);

        // Render them
        $view = new StatementView(['service' => $this->statementService]);

        if ($this->statementService->getSingle()) {
            $view = $view->renderGetSingle();
        } else {
            $view = $view->renderGet();
        }

        // Multipart responses are intentionally disabled for now
        //if (null === $attachments) {
            $this->setHeaders();
        Resource::jsonResponse(Resource::STATUS_OK, $view);
        //} else {
        //    $this->setHeaders();
        //    Resource::multipartResponse(Resource::STATUS_OK, $view, $attachments);
        //}
    }

    public function put()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('statements/write');

        // Do the validation
        $this->statementValidator->validateRequest($request);
        $this->statementValidator->validatePutRequest($request);

        // Save the statements
        $this->statementService->statementPut($request);

        //Always an empty response, unless there was an Exception
        $this->setHeaders();
        Resource::response(Resource::STATUS_NO_CONTENT);
    }

    public function post()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('statements/write');

        // Do the validation and multipart splitting
        $this->statementValidator->validateRequest($request);

        if ($request->isMultipart()) {
            $jsonRequest = $this->extractJsonRequestFromMultipart($request);
        } else {
            $jsonRequest = $request;
        }

        $this->statementValidator->validatePostRequest($jsonRequest);

        // Save the statements
        $this->statementService->statementPost($request);

        $view = new StatementView(['service' => $this->statementService]);
        $view = $view->renderPost();

        $this->setHeaders();
        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    public function options()
    {
        //Handle options request
        $this->getSlim()->response->headers->set('Allow', 'POST,PUT,GET,DELETE');
        Resource::response(Resource::STATUS_OK);
    }

    /**
     * @return \API\Service\Statement
     */
    public function getStatementService()
    {
        return $this->statementService;
    }

    /**
     * @param \API\Service\Statement $statementService
     */
    public function setStatementService($statementService)
    {
        $this->statementService = $statementService;
    }

    /**
     * @return \API\Validator\Statement
     */
    public function getStatementValidator()
    {
        return $this->statementValidator;
    }

    /**
     * @param \API\Validator\Statement $statementValidator
     */
    public function setStatementValidator($statementValidator)
    {
        $this->statementValidator = $statementValidator;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Extracts JSON request from multipart/mixed request.
     *
     * @param \Slim\Http\Request $request Request object
     *
     * @return \Slim\Http\Request Request object
     */
    protected function extractJsonRequestFromMultipart($request)
    {
        $jsonRequest = $request->parts()->get(0);

        return $jsonRequest;
    }

    /**
     * Extracts all attachment requests from main request.
     *
     * @param \Slim\Http\Request $request Whole request
     *
     * @return array Array of \Slim\Http\Request's that represent attachments
     */
    protected function extractAttachmentsFromRequest($request)
    {
        $requests = $request->parts()->all();
        array_shift($requests);

        return $requests;
    }

    /**
     * Sets specific headers for this request.
     */
    protected function setHeaders()
    {
    }
}
