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

namespace API\Service\Auth;

use API\Service;
use API\Controller;
use Slim\Http\Request;
use API\Util;
use League\Url\Url;
use API\HttpException as Exception;
use API\Service\Auth\Exception as AuthFailureException;
use API\Util\Collection;
use API\Config;

class OAuth extends Service implements AuthInterface
{
    public function addToken($expiresAt, $user, $client, array $scopes = [], $code = null)
    {
        $accessTokenDocument = $this->getStorage()->getOAuthStorage()->storeToken($expiresAt, $user, $client, $scopes, $code);

        return $accessTokenDocument;
    }

    public function fetchToken($accessToken)
    {
        $accessTokenDocument = $this->getStorage()->getOAuthStorage()->getToken($accessToken);

        return $accessTokenDocument;
    }

    public function deleteToken($accessToken)
    {
        $accessTokenDocument = $this->getStorage()->getOAuthStorage()->deleteToken($accessToken);

        return $accessTokenDocument;
    }

    public function expireToken($accessToken)
    {
        $accessTokenDocument = $this->getStorage()->getOAuthStorage()->expireToken($accessToken);

        return $accessTokenDocument;
    }

    public function addClient($name, $description, $redirectUri)
    {
        $clientDocument = $this->getStorage()->getOAuthClientsStorage()->addClient($name, $description, $redirectUri);

        return $clientDocument;
    }

    public function fetchClients()
    {
        $documentResult = $this->getStorage()->getOAuthClientsStorage()->getClients();

        return $documentResult;
    }

    // TODO 0.12.x: Move this logic a level higher, into Controllers (low-priority)
    /**
     * @param [type] $request [description]
     *
     * @return [type] [description]
     */
    public function authorizeGet()
    {
        // CSRF protection
        $_SESSION['csrfToken'] = Util\OAuth::generateCsrfToken();

        $parameters = (object)$this->getContainer()->get('parser')->getData()->getParameters();

        $requiredParams = ['response_type', 'client_id', 'redirect_uri', 'scope'];
        $this->validateRequiredParams($parameters, $requiredParams);

        $this->validateResponseType($parameters->response_type);

        // Get client by id
        $clientDocument = $this->getStorage()->getOAuthClientsStorage()->getClientById($parameters->client_id);

        $this->validateClientDocument($clientDocument);

        $this->validateRedirectUri($parameters->redirect_uri, $clientDocument);

        $scopeDocuments = [];
        $scopes = explode(',', $parameters->scope);
        $scopeDocuments = $this->validateAndMapScopes($scopes); // Throws exception if not a valid scope

        // Return client document object with added authorize request scopes
        $clientDocument->scopes = $scopeDocuments;
        return $clientDocument;
    }

    // TODO Add AuthorizeResult or something like that!
    /**
     * POST authorize data.
     *
     * @param   $request [description]
     *
     * @return [type] [description]
     */
    public function authorizePost()
    {
        $params = $this->getContainer()->get('parser')->getData()->getParameters();
        $postParams = $this->getContainer()->get('parser')->getData()->getPayload();

        $this->validateCsrf($postParams);
        $this->validateAction($postParams);

        if ($postParams->get('action') === 'accept') {
            $expiresAt = time() + Config::get(['xAPI', 'oauth', 'token_expiry_time']);
            // getClientById
            $clientDocument = $this->getStorage()->getOAuthClientsStorage()->getClientById($params->client_id);

            // getUserById --  $_SESSION['userId']
            $userDocument = $this->getStorage()->getUserStorage()->findById($_SESSION['userId']);

            $scopeDocuments = [];
            $scopes = explode(',', $params->scope);
            $scopeDocuments = $this->validateAndMapScopes($scopes);// throws exception if not a valid scope

            $code = Util\OAuth::generateToken();
            $token = $this->addToken($expiresAt, $userDocument, $clientDocument, $scopeDocuments, $code);
            $redirectUri = Url::createFromUrl($params->get('redirect_uri'));
            $redirectUri->getQuery()->modify(['code' => $token->getCode()]); //We could also use just $code
            return $redirectUri;
        } elseif ($postParams->get('action') === 'deny') {
            $redirectUri = Url::createFromUrl($params->get('redirect_uri'));
            $redirectUri->getQuery()->modify(['error' => 'User denied authorization!']);
            return $redirectUri;
        }
    }

    /**
     * Validates and retrieves access token
     *
     * @return array json document
     */
    public function accessTokenPost()
    {
        $params = $this->getContainer()->get('parser')->getData()->getPayload();
        $params = new Util\Collection($params);

        $requiredParams = ['grant_type', 'client_id', 'client_secret', 'redirect_uri', 'code'];

        $this->validateRequiredParams($params, $requiredParams);
        $this->validateGrantType($params['grant_type']);

        // getTokenWithOneTimeCode($params)
        $tokenDocument = $this->getStorage()->getOAuthStorage()->getTokenWithOneTimeCode($params);

        return $tokenDocument;
    }

    public function extractToken(Request $request)
    {
        $tokenHeader = $request->getHeaderLine('Authorization');

        if ($tokenHeader && preg_match('/Bearer\s*([^\s]+)/', $tokenHeader, $matches)) {
            $tokenHeader = $matches[1];
        } else {
            $tokenHeader = false;
        }
        $tokenParam = $request->getParam('access_token', false);
        // At least one (and only one) of client credentials method required.
        if (!$tokenHeader && !$tokenParam) {
            throw new AuthFailureException('The request is missing a required parameter.', Controller::STATUS_BAD_REQUEST);
        } elseif ($tokenHeader && $tokenParam) {
            throw new AuthFailureException('The request includes multiple credentials.', Controller::STATUS_BAD_REQUEST);
        }

        $accessToken = $tokenHeader
            ?: $tokenParam;

        try {
            $tokenDocument = $this->fetchToken($accessToken);
        } catch (\Exception $e) {
            throw new AuthFailureException('Access token invalid.');
        }

        return $this;
    }

    /**
     * Validates and compiles a document of given scopes
     * @return array collection of mapo
     */
    private function validateAndMapScopes($scopes)
    {
        $auth = $this->getContainer()->get('auth');
        $scopeDocuments = [];

        foreach ($scopes as $scope) {
            // Get scope by name
            $scopeDocument = $auth->getAuthScope($scope);

            if (!$scopeDocument) {
                throw new Exception('Invalid scope given!', Controller::STATUS_BAD_REQUEST);
            }
            $user = $this->getStorage()->getUserStorage()->findById($_SESSION['userId']);
            if (!in_array($scope, $user->permissions)) {
                throw new Exception('User does not have enough permissions for requested scope!', Controller::STATUS_BAD_REQUEST);
            }
            $scopeDocuments[$scope] = $scopeDocument;
        }

        return $scopeDocuments;
    }

    private function validateCsrf($params)
    {
        // CSRF protection
        if (!isset($params['csrfToken']) || !isset($_SESSION['csrfToken']) || ($params['csrfToken'] !== $_SESSION['csrfToken'])) {
            throw new Exception('Invalid CSRF token.', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateAction($params)
    {
        if ($params['action'] !== 'accept' && $params['action'] !== 'deny') {
            throw new Exception('Invalid.', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateRequiredParams($params, $requiredParams)
    {
        //TODO 0.11.x: Use GraphQL to validate these params
        foreach ($requiredParams as $requiredParam) {
            if (!isset($params->{$requiredParam})) {
                throw new Exception('Parameter '.$requiredParam.' is missing!', Controller::STATUS_BAD_REQUEST);
            }
        }
    }

    private function validateResponseType($responseType)
    {
        if ($responseType !== 'code') {
            throw new \Exception('Invalid response_type specified.', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateRedirectUri($redirectUri, $clientDocument)
    {
        if ($redirectUri !== $clientDocument->redirectUri) {
            throw new \Exception('Redirect_uri mismatch!', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateGrantType($grantType)
    {
        if ($grantType !== 'authorization_code') {
            throw new \Exception('Invalid grant_type specified.', Controller::STATUS_BAD_REQUEST);
        }
    }

    public function validateClientDocument($clientDocument)
    {
        if (null === $clientDocument) {
            throw new \Exception('Invalid client_id', Controller::STATUS_BAD_REQUEST);
        }
    }
}
