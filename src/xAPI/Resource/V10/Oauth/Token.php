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

namespace API\Resource\V10\Oauth;

use API\Resource;
use API\Service\Auth\OAuth as OAuthService;
use API\View\V10\OAuth\AccessToken as AccessTokenView;

class Token extends Resource
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
        $this->oAuthService = new OAuthService($this->getSlim());
    }

    public function post()
    {
        $request = $this->getSlim()->request();

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->oAuthService->accessTokenPost($request);
        // Authorization is always requested
        $view = new AccessTokenView(['service' => $this->oAuthService]);
        $view = $view->renderGet();
        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    public function options()
    {
        //Handle options request
        $this->getSlim()->response->headers->set('Allow', 'POST');
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
}
