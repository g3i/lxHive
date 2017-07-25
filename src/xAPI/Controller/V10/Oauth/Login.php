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

namespace API\Controller\V10\Oauth;

use API\Controller;
use API\Service\Auth\OAuth as OAuthService;
use API\Service\User as UserService;
use API\View\V10\OAuth\Login as LoginView;
use API\Util\OAuth;

class Login extends Controller
{
    /**
     * @var \API\Service\Auth\OAuth
     */
    private $oAuthService;

    /**
     * @var \API\Service\User
     */
    private $userService;

    /**
     * Get agent profile service.
     */
    public function init()
    {
        $this->oAuthService = new OAuthService($this->getContainer());
        $this->userService = new UserService($this->getContainer());
        OAuth::loadSession();
    }

    public function get()
    {
        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        if (!$this->userService->loggedIn()) {
            $this->userService->loginGet();

            // Authorization is always requested
            $view = new LoginView($this->getResponse(), $this->getContainer());

            $view = $view->renderGet();

            return $this->response(Controller::STATUS_OK, $view);
        } else {
            // Redirect to authorization
            $redirectUrl = $this->getContainer()->getUrl();
            $redirectUrl->getPath()->remove('login');
            $redirectUrl->getPath()->append('authorize');
            $this->setResponse($this->getResponse()->withHeader('Location', $redirectUrl));
            return $this->response(Controller::STATUS_FOUND);
        }
    }

    public function post()
    {
        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        // Authorization is always requested
        try {
            // This sets the session for the user, otherwise throws an exception!
            $this->userService->loginPost();
            $redirectUrl = $this->getContainer()['url'];
            $redirectUrl->getPath()->remove('login');
            $redirectUrl->getPath()->append('authorize');
            $this->setResponse($this->getResponse()->withHeader('Location', $redirectUrl));
            return $this->response(Controller::STATUS_FOUND);
        } catch (\Exception $e) {
            $view = new LoginView($this->getResponse(), $this->getContainer());
            $view = $view->renderGet();
            return $this->response(Controller::STATUS_UNAUTHORIZED, $view);
        }
    }

    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'POST,GET'));
        return $this->response(Controller::STATUS_OK);
    }

    /**
     * Gets the value of oAuthService.
     *
     * @return \API\Service\Auth\OAuth
     */
    public function getOAuthService()
    {
        return $this->oAuthService;
    }

    /**
     * Gets the value of userService.
     *
     * @return \API\Service\User
     */
    public function getUserService()
    {
        return $this->userService;
    }
}
