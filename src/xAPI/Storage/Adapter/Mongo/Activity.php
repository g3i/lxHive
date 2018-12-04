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

namespace API\Storage\Adapter\Mongo;

use API\Storage\SchemaInterface;
use API\Storage\Query\ActivityInterface;

use API\Controller;
use API\Storage\Provider;

use API\Storage\AdapterException;

class Activity extends Provider implements ActivityInterface, SchemaInterface
{
    const COLLECTION_NAME = 'activities';

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
            'name' => 'id.unique',
            'key'  => [
                'id' => 1
            ],
            'unique' => true,
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function install()
    {
        $container = $this->getContainer()->get('storage');
        $container->executeCommand(['create' => self::COLLECTION_NAME]);
        $container->createIndexes(self::COLLECTION_NAME, $this->indexes);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchById($id)
    {
        $storage = $this->getContainer()->get('storage');
        $expression = $storage->createExpression();

        $expression->where('id', $id);

        if ($storage->count(self::COLLECTION_NAME, $expression) === 0) {
            throw new AdapterException('Activity does not exist.', Controller::STATUS_NOT_FOUND);
        }

        $document = $storage->findOne(self::COLLECTION_NAME, $expression);

        return $document;
    }
}
