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

namespace API\Extensions\ExtendedQuery\Resource\V10;

use API\Resource;
use API\Extensions\ExtendedQuery\Service\Statement as ExtendedStatementService;
use API\Extensions\ExtendedQuery\View\V10\ProjectedStatement as ProjectedStatementView;

class ExtendedQuery extends Resource
{
    /**
     * @var ExtendedStatementService
     */
    private $extendedStatementService;

    public function init()
    {
        $this->extendedStatementService = new ExtendedStatementService($this->getContainer());
    }

    /**
     * Handle the query GET request.
     */
    public function get()
    {
        $request = $this->getContainer()->request();

        // Check authentication
        //$this->getContainer()->auth->checkPermission('statements/querybuilder');

        $documentResult = $this->getExtendedStatementService()->statementGet();

        // Render them
        $view = new ProjectedStatementView($this->getResponse(), $this->getContainer());

        $view = $view->render($documentResult);

        return $this->jsonResponse(Resource::STATUS_OK, $view);
    }

    /**
     * Handle the query POST request.
     */
    public function post()
    {
        $request = $this->getContainer()->request();

        // Check authentication
        $this->getContainer()->auth->checkPermission('statements/querybuilder');

        // Load the statements - this needs to change, drastically, as it's garbage
        $documentResult = $this->getExtendedStatementService()->statementPost($request);

        // Render them
        $view = new ProjectedStatementView($this->getResponse(), $this->getContainer());

        $view = $view->render($documentResult);

        return $this->jsonResponse(Resource::STATUS_OK, $view);
    }

    public function options()
    {
        // Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'POST,HEAD,GET,OPTIONS'));
        return $this->response(Resource::STATUS_OK);
    }

    /**
     * Gets the value of extendedStatementService.
     *
     * @return ExtendedStatementService
     */
    public function getExtendedStatementService()
    {
        return $this->extendedStatementService;
    }
}
