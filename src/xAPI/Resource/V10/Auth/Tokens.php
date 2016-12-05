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

namespace API\Resource\V10\Auth;

use API\Resource;
use API\Service\Auth\Basic as BasicTokenService;
use API\View\V10\BasicAuth\AccessToken as AccessTokenView;

class Tokens extends Resource
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
        $this->setAccessTokenService(new BasicTokenService($this->getSlim()));
    }

    public function get()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->accessTokenService->accessTokenGet($request);

        // Render them
        $view = new AccessTokenView(['service' => $this->accessTokenService]);

        $view = $view->render();

        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    public function post()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->accessTokenService->accessTokenPost($request);

        // Render them
        $view = new AccessTokenView(['service' => $this->accessTokenService]);

        $view = $view->render();

        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    public function put()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->accessTokenService->accessTokenPut($request);

        // Render them
        $view = new AccessTokenView(['service' => $this->accessTokenService]);

        $view = $view->render();

        Resource::jsonResponse(Resource::STATUS_OK, $view);
    }

    public function delete()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('super');

        // Do the validation - TODO!!!
        //$this->statementValidator->validateRequest($request);
        //$this->statementValidator->validatePutRequest($request);

        $this->accessTokenService->accessTokenDelete($request);

        Resource::response(Resource::STATUS_NO_CONTENT);
    }

    public function options()
    {
        //Handle options request
        $this->getSlim()->response->headers->set('Allow', 'POST,PUT,GET,DELETE');
        Resource::response(Resource::STATUS_OK);
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

    /**
     * Sets the value of accessTokenService.
     *
     * @param \API\Service\AccessToken $accessTokenService the access token service
     *
     * @return self
     */
    public function setAccessTokenService(\API\Service\Auth\Basic $accessTokenService)
    {
        $this->accessTokenService = $accessTokenService;

        return $this;
    }
}
