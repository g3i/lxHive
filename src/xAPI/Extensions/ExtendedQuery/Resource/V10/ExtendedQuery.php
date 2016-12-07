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

namespace API\Extensions\ExtendedQuery\Resource\V10;

use API\Resource;
use API\Extensions\ExtendedQuery\Service\Statement as ExtendedStatementService;
//use API\Extensions\ExtendedQuery\Validator\V10\StatementQueryBuilder as QueryBuilderValidator;
use API\Extensions\ExtendedQuery\View\V10\ProjectedStatement as ProjectedStatementView;

class ExtendedQuery extends Resource
{
    /**
     * @var ExtendedStatementService
     */
    private $extendedStatementService;

    /**
     * @var StatementQueryBuilderValidator
     */
    private $statementQueryBuilderValidator;

    public function init()
    {
        $this->setExtendedStatementService(new ExtendedStatementService($this->getSlim()));
        //$this->setStatementQueryBuilderValidator(new StatementQueryBuilderValidator());
        //$this->getStatementQueryBuilderValidator()->setDefaultSchemaValidator();
    }

    /**
     * Handle the query GET request.
     */
    public function get()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('statements/querybuilder');

        // Do the validation
        //$this->getStatementQueryBuilderValidator()->validateRequest($request);
        //$this->getStatementQueryBuilderValidator()->validateGetRequest($request);

        // Load the statements - this needs to change, drastically, as it's garbage
        $this->getExtendedStatementService()->statementGet($request);

        // Render them
        $view = new ProjectedStatementView(['service' => $this->getExtendedStatementService()]);

        $view = $view->render();

        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    /**
     * Handle the query POST request.
     */
    public function post()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('statements/querybuilder');

        // Do the validation
        //$this->getStatementQueryBuilderValidator()->validateRequest($request);
        //$this->getStatementQueryBuilderValidator()->validateGetRequest($request);

        // Load the statements - this needs to change, drastically, as it's garbage
        $this->getExtendedStatementService()->statementPost($request);

        // Render them
        $view = new ProjectedStatementView(['service' => $this->getExtendedStatementService()]);

        $view = $view->render();

        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    public function options()
    {
        // Handle options request
        $this->getSlim()->response->headers->set('Allow', 'HEAD, GET, POST, OPTIONS');
        Resource::response(Resource::STATUS_OK);
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

    /**
     * Sets the value of extendedStatementService.
     *
     * @param ExtendedStatementService $extendedStatementService the extended statement service
     *
     * @return self
     */
    private function setExtendedStatementService(ExtendedStatementService $extendedStatementService)
    {
        $this->extendedStatementService = $extendedStatementService;

        return $this;
    }

    /**
     * Gets the value of statementQueryBuilderValidator.
     *
     * @return StatementQueryBuilderValidator
     */
    //public function getStatementQueryBuilderValidator()
    //{
    //    return $this->statementQueryBuilderValidator;
    //}

    /**
     * Sets the value of statementQueryBuilderValidator.
     *
     * @param StatementQueryBuilderValidator $statementQueryBuilderValidator the statement query builder validator
     *
     * @return self
     */
    //private function setStatementQueryBuilderValidator(StatementQueryBuilderValidator $statementQueryBuilderValidator)
    //{
    //    $this->statementQueryBuilderValidator = $statementQueryBuilderValidator;
    //
    //    return $this;
    //}
}
