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

namespace API\Service;

use API\Service;
use API\Resource;
use API\Util\OAuth;
use API\Util\Rememberme\MongoStorage as RemembermeMongoStorage;
use Slim\Helper\Set;
use Sokil\Mongo\Cursor;
use Birke\Rememberme;
use API\HttpException as Exception;

class User extends Service
{
    // Will be deprecated with UserResult class
    /**
     * Is this a single user fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * Any errors that might've ocurred are stored here.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Logs the user in.
     *
     * @return \API\Document\User The user document
     */
    public function loginGet($request)
    {
        // CSRF protection
        $_SESSION['csrfToken'] = OAuth::generateCsrfToken();
    }

    /**
     * Logs the user in.
     *
     * @return \API\Document\User The user document
     */
    public function loginPost($request)
    {
        // TODO: This will be fetched from Parser class in future!
        $params = new Set($request->post());

        $this->validateCsrf($params);
        $this->validateRequiredParameters($params);

        $document = $this->getStorage()->getUserStorage()->findByEmailAndPassword($params->get('email'), $params->get('password'));

        if (null === $document) {
            $errorMessage = 'Invalid login attempt. Try again!';
            $this->errors[] = $errorMessage;
            throw new \Exception($errorMessage, Resource::STATUS_UNAUTHORIZED);
        }

        $this->single = true;
        $this->cursor = [$document];

        // Set the session
        $_SESSION['userId'] = $document->getId();
        $_SESSION['expiresAt'] = time() + 3600; //1 hour

        // TODO: Remove this remember me helper/library
        // Set the Remember me cookie
        $rememberMeStorage = new RemembermeMongoStorage($this->getDocumentManager());
        $rememberMe = new Rememberme\Authenticator($rememberMeStorage);


        if ($params->has('rememberMe')) {
            $rememberMe->createCookie($document->getId());
        } else {
            $rememberMe->clearCookie();
        }

        return $document;
    }

    public function loggedIn()
    {
        $rememberMeStorage = new RemembermeMongoStorage($this->getDocumentManager());
        $rememberMe = new Rememberme\Authenticator($rememberMeStorage);
        
        if (isset($_SESSION['userId']) && isset($_SESSION['expiresAt']) && $_SESSION['expiresAt'] > time()) {
            $_SESSION['expiresAt'] = time() + 3600; //Renew session on every activity
            return true;
        } else if (!empty($_COOKIE[$rememberMe->getCookieName()]) && $rememberMe->cookieIsValid()) { // Remember me cookie
            $loginresult = $rememberMe->login();
            if ($loginresult) {
                // Load user into session and return true
                // Set the session
                $_SESSION['userId'] = $loginresult;
                $_SESSION['expiresAt'] = time() + 3600; //1 hour
                $_SESSION['rememberedByCookie'] = true;
            } else {
                if ($rememberMe->loginTokenWasInvalid()) {
                    throw new \Exception('Remember me cookie invalid!', Resource::STATUS_BAD_REQUEST);
                }
            }
        } else {
            return false;
        }
    }

    public function findById($id)
    {
        $document = $this->getStorage()->getUserStorage()->findById($id);

        return $document;
    }

    public function getLoggedIn()
    {
        $userId = $_SESSION['userId'];
        $userDocument = $this->findById($userId);

        return $userDocument;
    }

    public function addUser($email, $password, $permissions)
    {
        $userDocument = $this->getStorage()->getUserStorage()->addUser($email, $password, $permissions);

        $this->single = true;
        $this->cursor = [$userDocument];

        return $userDocument;
    }

    public function fetchAll()
    {
        $cursor = $userDocument = $this->getStorage()->getUserStorage()->fetchAll();

        $this->cursor = $cursor;

        return $this;
    }

    public function fetchAvailablePermissions()
    {
        $cursor = $userDocument = $this->getStorage()->getUserStorage()->fetchAvailablePermissions();

        $this->cursor = $cursor;

        return $this;
    }

    private function validateCsrf($params)
    {
        // CSRF protection
        if (!isset($params['csrfToken']) || !isset($_SESSION['csrfToken']) || ($params['csrfToken'] !== $_SESSION['csrfToken'])) {
            throw new Exception('Invalid CSRF token.', Resource::STATUS_BAD_REQUEST);
        }
    }

    private function validateRequiredParameters($params)
    {
        // This could be in JSON schema as well :)
        if (!isset($params['email']) || !isset($params['password'])) {
            throw new Exception('Username or password missing!', Resource::STATUS_BAD_REQUEST);
        }
    }
            

    /**
     * Gets the Users.
     *
     * @return array
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Sets the Users.
     *
     * @param array $users the users
     *
     * @return self
     */
    public function setUsers(array $users)
    {
        $this->users = $users;

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
     * Gets the Is this a single user fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single user fetch?.
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
     * Gets the Any errors that might've ocurred are stored here.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Sets the Any errors that might've ocurred are stored here.
     *
     * @param array $errors the errors
     *
     * @return self
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;

        return $this;
    }
}
