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

interface AgentProfileInterface extends QueryInterface
{
    /**
     * Find single record by Mongo ObjectId
     *
     * @param array $parameters map of query params
     *
     * @return \API\DocumentInterface
     */
    public function getFiltered($parameters);

    /**
     * Upsert a single record
     *
     * @param array $parameters map of query params
     * @param stdClass $profileObject
     *
     * @return \API\DocumentInterface
     */
    public function post($parameters, $profileObject);

    /**
     * Upsert a single record
     *
     * @param array $parameters map of query params
     * @param stdClass $profileObject
     *
     * @return \API\DocumentInterface
     */
    public function put($parameters, $profileObject);

    /**
     * Delete a single record
     *
     * @param array $parameters map of query params
     * @return \API\Storage\Query\API\DeletionResult
     */
    public function delete($parameters);
}
