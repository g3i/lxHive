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

namespace API\Service;

use API\Service;
use API\Controller;
use API\Util\OAuth;
use API\HttpException as Exception;
use API\Util\Collection;

class User extends Service
{
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
    public function loginGet()
    {
        // CSRF protection
        $_SESSION['csrfToken'] = OAuth::generateCsrfToken();
    }

    /**
     * Logs the user in.
     *
     * @return \API\Document\User The user document
     */
    public function loginPost()
    {
        $parameters = (object)$this->getContainer()->get('parser')->getData()->getPayload();

        $this->validateCsrf($parameters);
        $this->validateRequiredParameters($parameters);

        $document = $this->getStorage()->getUserStorage()->findByEmailAndPassword($parameters->email, $parameters->password);

        if (null === $document) {
            $errorMessage = 'Invalid login attempt. Try again!';
            $this->errors[] = $errorMessage;
            throw new \Exception($errorMessage, Controller::STATUS_UNAUTHORIZED);
        }

        // Set current user auth
        $_SESSION['userId'] = (string)$document->_id;
        $_SESSION['expiresAt'] = time() + 3600; //1 hour

        return $document;
    }

    public function loggedIn()
    {
        if (isset($_SESSION['userId']) && isset($_SESSION['expiresAt']) && $_SESSION['expiresAt'] > time()) {
            $_SESSION['expiresAt'] = time() + 3600; //Renew session on every activity
            return true;
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

    public function addUser($name, $description, $email, $password, $permissions)
    {
        $userDocument = $this->getStorage()->getUserStorage()->addUser($name, $description, $email, $password, $permissions);

        return $userDocument;
    }

    public function fetchAll()
    {
        $documentResult = $userDocument = $this->getStorage()->getUserStorage()->fetchAll();

        return $documentResult;
    }

    public function hasEmail($email)
    {
        return $this->getStorage()->getUserStorage()->hasEmail($email);
    }

    private function validateCsrf($params)
    {
        // CSRF protection
        if (!isset($params->csrfToken) || !isset($_SESSION['csrfToken']) || ($params->csrfToken !== $_SESSION['csrfToken'])) {
            throw new Exception('Invalid CSRF token.', Controller::STATUS_BAD_REQUEST);
        }
    }

    private function validateRequiredParameters($params)
    {
        // This could be in JSON schema as well :)
        if (!isset($params->email) || !isset($params->password)) {
            throw new Exception('Username or password missing!', Controller::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
