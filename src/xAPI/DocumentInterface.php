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

namespace API;

interface DocumentInterface extends \JsonSerializable
{
    /**
     * Constructor
     *
     * @param array $data xAPI data
     * @param string $documentState EUNUM string of i/o state of the document (i.e 'TRUSTED', 'UNTRUSTED', etc..)
     * @param string $version xAPI version
     * @return void
     */
    public function __construct($data = [], $documentState = null, $version = null);

    /**
     * Get stored data
     *
     * @return array xAPI data
     */
    public function getData();

    /**
     * Get stored document state
     *
     * @return string i/o state of the document
     */
    public function getState();

    /**
     * Get stored xAPI version
     *
     * @return string
     */
    public function getVersion();

    /**
     * Get stored document state
     *
     * @return string i/o state of the document
     */
    public function toArray();
}
