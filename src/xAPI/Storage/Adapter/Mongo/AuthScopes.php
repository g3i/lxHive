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

namespace API\Storage\Adapter\Mongo;

use API\Storage\SchemaInterface;
use API\Storage\Query\AuthScopesInterface;

use API\Storage\Provider;

class AuthScopes extends Provider implements AuthScopesInterface, SchemaInterface
{
    const COLLECTION_NAME = 'authScopes';

    /**
     * @var array $indexes
     *
     * @see https://docs.mongodb.com/manual/reference/command/createIndexes/
     *  [
     *      name: <index_name>,
     *      key: [
     *          <key-value_pair>,
     *          <key-value_pair>,
     *          ...
     *      ],
     *      <option1-value_pair>,
     *      <option1-value_pair>,
     *      ...
     *  ],
     */
    private $indexes = [
        [
            'name' => 'name.unique',
            'key'  => [
                'name' => 1
            ],
            'unique' => true,
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function install()
    {
        $storage = $this->getContainer()['storage']->createIndexes(self::COLLECTION_NAME, $this->indexes);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    public function findById($id)
    {
        $storage = $this->getContainer()['storage'];
        $expression = $storage->createExpression();

        $expression->where('_id', $id);

        $result = $storage->findOne($id);

        return $result;
    }

    public function fetchAll()
    {
        $storage = $this->getContainer()['storage'];
        $cursor = $storage->find(self::COLLECTION_NAME);

        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }

    public function findByNames($names, $options = [])
    {
        $storage = $this->getContainer()['storage'];
        $cursor = $storage->find(self::COLLECTION_NAME, [
            'name' => ['$in' => $names]
        ], $options);

        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }

    public function getNames()
    {
        $storage = $this->getContainer()['storage'];
        $cursor = $storage->distinct(self::COLLECTION_NAME, 'name');

        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }

}
