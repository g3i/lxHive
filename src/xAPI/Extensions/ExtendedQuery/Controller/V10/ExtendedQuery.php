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

namespace API\Extensions\ExtendedQuery\Controller\V10;

use API\Controller;
use API\Extensions\ExtendedQuery\Service\Statement as ExtendedStatementService;
use API\Extensions\ExtendedQuery\View\V10\ProjectedStatement as ProjectedStatementView;

use API\Extensions\ExtensionException as Exception;

/**
 * Extension Controller class
 * @see \API\ControllerInterface
 */
class ExtendedQuery extends Controller
{
    /**
     * @var API\Extensions\ExtendedQuery\Service\Statement $extendedStatementService Servive instance
     */
    private $extendedStatementService;

    /**
     * Initialize controller
     * @return void
     */
    public function init()
    {
        $this->extendedStatementService = new ExtendedStatementService($this->getContainer());
    }

    /**
     * Process GET request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get()
    {
        $request = $this->getRequest();

        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('ext/extendedquery/statements');

        $documentResult = $this->getExtendedStatementService()->statementGet();

        // Render them
        $view = new ProjectedStatementView($this->getResponse(), $this->getContainer());

        $view = $view->render($documentResult);

        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    /**
     * Process POST request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post()
    {
        $request = $this->getRequest();

        // TODO: Move header validation in a json-schema
        if ($request->getMediaType() !== 'application/json') {
            throw new Exception('Media type specified in Content-Type header must be \'application/json\'!', Controller::STATUS_BAD_REQUEST);
        }

        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('ext/extendedquery/statements');

        // Load the statements - this needs to change, drastically, as it's garbage
        $documentResult = $this->getExtendedStatementService()->statementPost();

        // Render them
        $view = new ProjectedStatementView($this->getResponse(), $this->getContainer());

        $view = $view->render($documentResult);

        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    /**
     * Process OPTIONS request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function options()
    {
        // Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'POST,HEAD,GET,OPTIONS'));
        return $this->response(Controller::STATUS_OK);
    }

    /**
     * Get extendedStatementService instance
     * @return API\Extensions\ExtendedQuery\Service\Statement $extendedStatementService Servive instance
     */
    public function getExtendedStatementService()
    {
        return $this->extendedStatementService;
    }
}
