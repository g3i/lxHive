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
 *
 * Projected Usage
 *
 *  POST/PUT:
 *  $document = new \API\Document\Statement($parsedJson, 'UNTRUSTED', '1.0.3');
 *  $statement = $document->validate()->normalize()->document(); // validated and normalized stdClass, ready for storage, changes the state with each chain ['UNTRUSTED->VALIDTED->READY]
 *
 *  REST response
 *  $document = new \API\Document\Statement($mongoDocument, 'TRUSTED', '1.0.3');
 *  $document->validate()->normalize(); //deals with minor incositencies, will in future also remove meta properties
 *  $json = json_encode($document);
 *
 *  $document will have convenience methods and reveal the convenience methods of subproperties
 *  $document->isReferencing();
 *  $document->actor->isAgent();
 *  $document->object->isSubStatement();
 *
 *  etc..
 */

namespace API\Document;

use API\Bootstrap;
use API\Document;
use API\Controller;

// TODO 0.11.x: implement normalize, validate, etc. (GraphQL)

class AccessToken extends Document
{

    ////
    // Setters for new documents
    ////

    /**
     * Sets document property: name
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->data->name = $name;
    }

    /**
     * Sets document property: description
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->data->description = $description;
    }

    /**
     * Sets document property: expiresIn
     * @param int $expiresIn
     */
    public function setExpiresIn($expiresIn)
    {
        $until = \API\Util\Date::dateFromSeconds($expiresIn);
        $until = \API\Util\Date::dateStringToMongoDate($until);
        $this->setExpiresAt($until);
    }

    ////
    // Getters for stored documents
    ////

    /**
     * Gets document property: expiresIn
     * @return int period in seconds
     */
    public function getExpiresIn()
    {
        $dateTime = new \DateTime();
        if ($this->getExpiresAt() === null) {
            return null;
        } else {
            $dateTime->setTimestamp($this->getExpiresAt()->sec);
            $until = \API\Util\Date::secondsUntil($dateTime);

            return $until;
        }
    }

    ////
    // Checks/Validaton for stored documents
    ////

    /**
     * Check if fetched token document is expired
     * @return bool
     */
    public function isExpired()
    {
        if ($this->getExpired()) {
            return true;
        } elseif (null !== $this->getExpiresIn() && $this->getExpiresIn() <= 0) {
            $this->setExpired(true);
            return true;
        } else {
            return false;
        }
    }
}