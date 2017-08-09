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

namespace API\Storage\Query;

interface AuthScopesInterface extends QueryInterface
{
    /**
     * Find record by Mongo ObjectId
     * @param MongoDB\BSON\ObjectID $id
     *
     * @return \API\DocumentInterface|null
     */
    public function findById($id);

    /**
     * Find record by scope name
     * @param string $name scope name
     *
     * @return \API\DocumentInterface|null
     */
    public function findByName($name);

    /**
     * Find all records
     * @return \API\DocumentInterface
     */
    public function fetchAll();

    /**
     * Find records by scope names
     * @array $names array of scope names
     * @array $options map of \MongoDB\Driver\Query options
     *
     * @return \API\DocumentInterface
     */
    public function findByNames($names, $options);

    /**
     * Get distinct record of scope names
     *
     * @return \API\DocumentInterface
     */
    public function getNames();


    /**
     * Adds a new record
     * @param string $name
     * @param string $description
     *
     * @return \API\DocumentInterface
     */
    public function addScope($name, $description);
}
