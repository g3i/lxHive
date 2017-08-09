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

namespace API\Controller\V10\Auth;

use API\Controller;
use API\Service\Auth\Basic as BasicTokenService;
use API\View\V10\BasicAuth\AccessToken as AccessTokenView;

class Tokens extends Controller
{
    /**
     * @var \API\Service\AccessToken
     */
    private $accessTokenService;

    /**
     * Get agent profile service.
     */
    public function init()
    {
        $this->accessTokenService = new BasicTokenService($this->getContainer());
    }

    public function get()
    {
        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->accessTokenService->accessTokenGet();

        // Render them
        $view = new AccessTokenView($this->getResponse(), $this->getContainer());

        $view = $view->render();

        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    public function post()
    {
        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $accessTokenDocument = $this->accessTokenService->accessTokenPost();

        // Render them
        $view = new AccessTokenView($this->getResponse(), $this->getContainer());

        $view = $view->render($accessTokenDocument);

        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    public function put()
    {
        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->accessTokenService->accessTokenPut();

        // Render them
        $view = new AccessTokenView($this->getResponse(), $this->getContainer());

        $view = $view->render();

        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    public function delete()
    {
        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->accessTokenService->accessTokenDelete();

        return $this->response(Controller::STATUS_NO_CONTENT);
    }

    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'POST,PUT,GET,DELETE'));
        return $this->response(Controller::STATUS_OK);
    }

    /**
     * Gets the value of accessTokenService.
     *
     * @return \API\Service\AccessToken
     */
    public function getAccessTokenService()
    {
        return $this->accessTokenService;
    }
}
