<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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

class User extends Service
{
    /**
     * Users.
     *
     * @var array
     */
    protected $users;

    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

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
        $params = new Set($request->post());

        // CSRF protection
        if (!$params->has('csrfToken') || !isset($_SESSION['csrfToken']) || ($params->get('csrfToken') !== $_SESSION['csrfToken'])) {
            throw new \Exception('Invalid CSRF token.', Resource::STATUS_BAD_REQUEST);
        }

        // This could be in JSON schema as well :)
        if (!$params->has('email') || !$params->has('password')) {
            throw new \Exception('Username or password missing!', Resource::STATUS_BAD_REQUEST);
        }

        $collection  = $this->getDocumentManager()->getCollection('users');
        $cursor      = $collection->find();

        $cursor->where('email', $params->get('email'));
        $cursor->where('passwordHash', sha1($params->get('password')));

        $document = $cursor->current();

        if (null === $document) {
            $errorMessage = 'Invalid login attempt. Try again!';
            $this->errors[] = $errorMessage;
            throw new \Exception($errorMessage, Resource::STATUS_UNAUTHORIZED);
        }

        $this->single = true;
        $this->users = [$document];

        // Set the session
        $_SESSION['userId'] = $document->getId();
        $_SESSION['expiresAt'] = time() + 3600; //1 hour

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
            if(!empty($_COOKIE[$rememberMe->getCookieName()]) && !$rememberMe->cookieIsValid()) {
                return false;
            }
            $_SESSION['expiresAt'] = time() + 3600; //Renew session on every activity
            return true;
        } else if { // Remember me cookie
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

    public function addUser($email, $password, $permissions)
    {
        $collection  = $this->getDocumentManager()->getCollection('users');

        // Set up the User to be saved
        $userDocument = $collection->createDocument();

        $userDocument->setEmail($email);

        $passwordHash = sha1($password);
        $userDocument->setPasswordHash($passwordHash);

        foreach ($permissions as $permission) {
            $userDocument->addPermission($permission);
        }

        $userDocument->save();

        $this->single = true;
        $this->users = [$userDocument];

        return $userDocument;
    }

    public function fetchAll()
    {
        $collection  = $this->getDocumentManager()->getCollection('users');
        $cursor      = $collection->find();

        $this->cursor = $cursor;

        return $this;
    }

    public function fetchAvailablePermissions()
    {
        $collection  = $this->getDocumentManager()->getCollection('authScopes');
        $cursor      = $collection->find();

        $this->cursor = $cursor;

        return $this;
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
     * Sets the Cursor.
     *
     * @param cursor $cursor the cursor
     *
     * @return self
     */
    public function setCursor(Cursor $cursor)
    {
        $this->cursor = $cursor;

        return $this;
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
