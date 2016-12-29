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

namespace API\Resource\V10\Oauth;

use API\Resource;
use API\Service\Auth\OAuth as OAuthService;
use API\Service\User as UserService;
use API\View\V10\OAuth\Authorize as OAuthAuthorizeView;
use API\Util\OAuth;

class Authorize extends Resource
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

        if ($this->userService->loggedIn()) {
            $this->oAuthService->authorizeGet();
            // Authorization is always requested
            $view = new OAuthAuthorizeView(['service' => $this->oAuthService, 'userService' => $this->userService]);
            $view = $view->renderGet();
            Resource::response(Resource::STATUS_OK, $view);
        } else {
            // Redirect to login
            $redirectUrl = $this->getContainer()->url;
            $redirectUrl->getPath()->remove('authorize');
            $redirectUrl->getPath()->append('login');
            $this->getContainer()->response->headers->set('Location', $redirectUrl);
            Resource::response(Resource::STATUS_FOUND);
        }
    }

    public function post()
    {
        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        if ($this->userService->loggedIn()) {
            // Authorization is always requested
            $this->oAuthService->authorizePost();
            $redirectUri = $this->oAuthService->getRedirectUri();
            $this->getContainer()->response->headers->set('Location', $redirectUri);
            Resource::response(Resource::STATUS_FOUND);
        } else {
            // Unauthorized
            Resource::response(Resource::STATUS_UNAUTHORIZED);
        }
    }

    public function options()
    {
        //Handle options request
        $this->getContainer()->response->headers->set('Allow', 'POST,PUT,GET,DELETE');
        Resource::response(Resource::STATUS_OK);
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
