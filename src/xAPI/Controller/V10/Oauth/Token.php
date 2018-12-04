<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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
use API\View\V10\OAuth\AccessToken as AccessTokenView;

class Token extends Controller
{
    /**
     * @var \API\Service\Auth\OAuth
     */
    private $oAuthService;

    /**
     * Get agent profile service.
     */
    public function init()
    {
        $this->oAuthService = new OAuthService($this->getContainer());
    }

    public function post()
    {
        // TODO 0.11.x request validation

        $accessTokenDocument = $this->oAuthService->accessTokenPost();
        // Authorization is always requested
        $view = new AccessTokenView($this->getResponse(), $this->getContainer());
        $view = $view->render($accessTokenDocument);
        return $this->jsonResponse(Controller::STATUS_OK, $view);
    }

    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'POST'));
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
}
