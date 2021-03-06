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

namespace API\Storage\Query;

interface StatementInterface extends QueryInterface
{
    /**
     * Get statements using filters.
     * @param array map of query params
     *
     * @return StatementResult
     */
    public function get($parameters);

    /**
     * Find single statement by statementId (not ObjectId!)
     * @param string $statementId
     *
     * @return StatementResult|null
     */
    public function getById($statementId);

    /**
     * Insert single document with params[statementId]
     * @param array $parameters map of quer yparams
     *
     * @return StatementResult
     * @throws API\Storage\AdapterException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function put($parameters, $statementObject);

    /**
     * Transforms statementObject (parser) into a statementDocument (storage)
     * @param object $statementObject
     *
     * @return \API\DocumentInterface statement document
     * @throws \API\Storage\AdapterException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function transformForInsert($statementObject);


    /**
     * Insert single document
     * @param object $statementObject
     *
     * @return StatementResult|null
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function insertOne($statementObject);

    /**
     * Insert collection of documents
     * @param array $statementObjects
     *
     * @return StatementResult
     * @throws API\Storage\AdapterException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function insertMultiple($statementObjects);


    /**
     * Ensures that deletion of statements is impossible by throwing always an exception
     *
     * @throws API\Storage\AdapterException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function delete($parameters);
}
