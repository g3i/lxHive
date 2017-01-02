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

interface StatementInterface
{
    /**
     * Get statements using filters.
     *
     * @param array|ArrayAccess $parameters xAPI parameters that determine the statements to be returned
     *
     * @return StatementResult The StatementResult object pertaining to this query
     */
    public function get($parameters);

    /**
     * Get statement by ID.
     *
     * @param string $statementId The Statement id
     *
     * @return StatementResult The StatementResult object pertaining to this query
     */
    public function getById($statementId);

    /**
     * @param  $statementId
     *
     * @return bool
     */
    public function statementWithIdExists($statementId);

    public function post($statementObject);

    public function put($parameters, $statementObject);

    public function delete($parameters);
}
