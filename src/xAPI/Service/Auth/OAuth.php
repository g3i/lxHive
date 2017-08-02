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

class OAuth extends Service implements AuthInterface
{
    /**
     * Access tokens.
     *
     * @var array
     */
    protected $accessTokens;

    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

    /**
     * Is this a single access token fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * The relevant client(s).
     *
     * @var \API\Document\Auth\OAuthClient
     */
    protected $client;

    /**
     * The relevant scopes.
     *
     * @var array
     */
    protected $scopes;

    /**
     * The relevant token.
     *
     * @var \API\Document\Auth\OAuthToken
     */
    protected $token;

    /**
     * The relevant redirectUri.
     *
     * @var string
     */
    protected $redirectUri;

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

    public function addScope($name, $description)
    {
        $scopeDocument = $this->getStorage()->getAuthScopesStorage()->addScope($name, $description);

        return $scopeDocument;
    }

    /**
     * get scope document by scope name
     */
    public function getScopeByName($name)
    {
        $scope = $this->getStorage()->getAuthScopesStorage()->findByName($name);
        return $scope;
    }

    /**
     * @param [type] $request [description]
     *
     * @return [type] [description]
     */
    public function authorizeGet($request)
    {
        // CSRF protection
        $_SESSION['csrfToken'] = Util\OAuth::generateCsrfToken();

        $parameters = $this->getContainer()['parser']->getData()->getParameters();

        $requiredParams = ['response_type', 'client_id', 'redirect_uri', 'scope'];

        $this->validateRequiredParams($params, $requiredParams);

        $this->validateResponseType($params['responseType']);

        // get client by id
        $clientDocument = $this->getStorage()->getOAuthClientsStorage()->getClientById($params['client_id']);

        $this->validateClientDocument($clientDocument);

        $this->validateRedirectUri($params['redirect_uri'], $clientDocument);

        $scopeDocuments = [];
        $scopes = explode(',', $params['scope']);
        foreach ($scopes as $scope) {
            // get scope by name
            $scopeDocument = $this->getScopeByName($scope);
            if (null !== $scopeDocument) {
                $this->validateScopeDocument($scopeDocument);
                $scopeDocuments[] = $scopeDocument;
            }
        }

        $this->client = $clientDocument;
        $this->scopes = $scopeDocuments;
    }

    // TODO Add AuthorizeResult or something like that!
    /**
     * POST authorize data.
     *
     * @param   $request [description]
     *
     * @return [type] [description]
     */
    public function authorizePost($request)
    {
        $params = $this->getContainer()['parser']->getData()->getParameters();

        $postParams = new Collection($request->post());
        $params = new Collection($request->get());

        $this->validateCsrf($postParams);
        $this->validateAction($postParams);

        // TODO: Improve this, load stuff from config, add documented error codes, separate stuff into functions, etc.
        if ($postParams->get('action') === 'accept') {
            $expiresAt = time() + 3600;
            // get client by id
            $clientDocument = $this->getStorage()->getOAuthClientsStorage()->getClientById($params->get('client_id'));

            // getuserbyid --  $_SESSION['userId']
            $userDocument = $this->getStorage()->getUserStorage()->findById($_SESSION['userId']);

            $scopeDocuments = [];
            $scopes = explode(',', $params->get('scope'));
            foreach ($scopes as $scope) {
                $scopeDocument = $this->getScopeByName($scope);
                if (null !== $scopeDocument) {
                    $this->validateScopeDocument($scopeDocument);
                    $scopeDocuments[] = $scopeDocument;
                }
            }
            $code = Util\OAuth::generateToken();
            $token = $this->addToken($expiresAt, $userDocument, $clientDocument, $scopeDocuments, $code);
            $this->token = $token;
            $redirectUri = Url::createFromUrl($params->get('redirect_uri'));
            $redirectUri->getQuery()->modify(['code' => $token->getCode()]); //We could also use just $code
            $this->redirectUri = $redirectUri;
        } elseif ($postParams->get('action') === 'deny') {
            $redirectUri = Url::createFromUrl($params->get('redirect_uri'));
            $redirectUri->getQuery()->modify(['error' => 'User denied authorization!']);
            $this->redirectUri = $redirectUri;
        }
    }

    /**
     * Validates and retrieves access token
     *
     * @return array json document
     */
    public function accessTokenPost()
    {
        $params = $this->getContainer()['parser']->getData()->getPayload();
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

    public function fetchScopes()
    {
        $accessToken = current($this->accessTokens);
        // Fetch scopes for access token
        $tokenDocument = $this->getStorage()->getOAuthStorage()->getTokenWithOneTimeCode($params);

        return $tokenDocument;
    }

    public function hasPermission($permissionName)
    {
        foreach ($this->fetchScopes() as $scope) {
            if ($scope['name'] === $permissionName || $scope['name'] === 'super') {
                return true;
            }
            if ($permissionName !== 'super' && $scope['name'] === 'all') {
                return true;
            }
        }
        return false;
    }

    public function checkPermission($permissionName)
    {
        if ($this->hasPermission($permissionName)) {
            return true;
        } else {
            return new \Exception('Permission denied.', Controller::STATUS_FORBIDDEN);
        }
    }

    private function validateScopeDocument($scopeDocument)
    {
        if (null === $scopeDocument) {
            throw new Exception('Invalid scope given!', Controller::STATUS_BAD_REQUEST);
        }
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
        //TODO: Use json-schema validator
        foreach ($requiredParams as $requiredParam) {
            if (!isset($params[$requiredParam])) {
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
        if ($params['redirect_uri'] !== $clientDocument->getRedirectUri()) {
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

    /**
     * Gets the Access tokens.
     *
     * @return array
     */
    public function getAccessTokens()
    {
        return $this->accessTokens;
    }

    /**
     * Sets the Access tokens.
     *
     * @param array $accessTokens the access tokens
     *
     * @return self
     */
    public function setAccessTokens(array $accessTokens)
    {
        $this->accessTokens = $accessTokens;

        return $this;
    }

    /**
     * Gets the Cursor.
     *
     * @return cursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Sets the Cursor.
     *
     * @param cursor $cursor the cursor
     *
     * @return self
     */
    public function setCursor($cursor)
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Gets the Is this a single access token fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single access token fetch?.
     *
     * @param bool $single the is single
     *
     * @return self
     */
    public function setSingle($single)
    {
        $this->single = $single;

        return $this;
    }

    /**
     * Gets the The relevant client(s).
     *
     * @return \API\Document\Auth\OAuthClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the The relevant client(s).
     *
     * @param \API\Document\Auth\OAuthClient $client the client
     *
     * @return self
     */
    public function setClient(\API\Document\Auth\OAuthClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Gets the The relevant scopes.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Sets the The relevant scopes.
     *
     * @param array $scopes the scopes
     *
     * @return self
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * Gets the The relevant token.
     *
     * @return \API\Document\Auth\OAuthToken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the The relevant token.
     *
     * @param \API\Document\Auth\OAuthToken $token the token
     *
     * @return self
     */
    public function setToken(\API\Document\Auth\OAuthToken $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Gets the The relevant redirectUri.
     *
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * Sets the The relevant redirectUri.
     *
     * @param string $redirectUri the redirect uri
     *
     * @return self
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }
}
