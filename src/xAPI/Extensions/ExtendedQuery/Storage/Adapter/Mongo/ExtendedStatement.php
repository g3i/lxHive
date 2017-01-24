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

namespace API\Extensions\ExtendedQuery\Storage\Adapter\MongoLegacy;

use API\Extensions\ExtendedQuery\Storage\Query\ExtendedStatementInterface;
use API\Storage\Adapter\Mongo\Base;
use API\Storage\Adapter\Query\StatementResult;
use API\Resource;

class ExtendedStatement extends Base implements ExtendedStatementInterface
{
    public function extendedQuery()
    {
        $parameters = $this->getContainer()['parser']->getData()->getParameters();
        $storage = $this->getContainer()['storage'];
        $collection = 'statements';

        $queryOptions = [];

        // New StatementResult for non-single statement queries
        $statementResult = new StatementResult();

        // Merge in query
        $expression = $storage->createExpression();
        $query = $parameters['query'];
        if (is_string($query)) {
            $query = json_decode($query, true);
        }
        $expression->fromArray($query);

        // Add projection
        if (isset($parameters['projection'])) {
            $fields = $parameters['projection'];
            if (is_string($fields)) {
                $fields = json_decode($fields, true);
            }
            foreach ($fields as $field => $value) {
                if (strpos($field, 'statement.') !== 0) {
                    throw new \Exception('Invalid projection parameters!.', Resource::STATUS_BAD_REQUEST);
                }
            }
            $fields = array_keys($fields);
            array_unshift($fields, '_id');
            $queryOptions['projection'] = $fields;
        } else {
            $queryOptions['projection'] = ['_id', 'statement'];
        }

        // Count before paginating
        $count = $storage->count($collection, $expression, $queryOptions);
        $statementResult->setTotalCount($count);

        // Handle pagination
        if (isset($parameters['since_id'])) {
            $id = new \MongoId($parameters['since_id']);
            $cursor->whereGreater('_id', $id);
        }

        if (isset($parameters['until_id'])) {
            $id = new \MongoId($parameters['until_id']);
            $cursor->whereLess('_id', $id);
        }

        if (isset($parameters['ascending']) && $parameters['ascending'] === 'true') {
            $statementResult->setSortDescending(false);
            $statementResult->setSortAscending(true);
            $queryOptions['sort'] = ['_id' => 1];
        } else {
            $statementResult->setSortDescending(true);
            $statementResult->setSortAscending(false);
            $queryOptions['sort'] = ['_id' => -1];
        }

        if (isset($parameters['limit']) && $parameters['limit'] < $this->getConfig()->get('xAPI.statement_get_limit') && $parameters['limit'] > 0) {
            $limit = $parameters['limit'];
        } else {
            $limit = $this->getConfig()->get('xAPI.statement_get_limit');
        }

        $queryOptions['limit'] = $limit;

        // Remaining includes the current page!
        $count = $storage->count($collection, $expression, $queryOptions);
        $statementResult->setRemainingCount($count);

        if ($statementResult->getRemainingCount() > $limit) {
            $statementResult->setHasMore(true);
        } else {
            $statementResult->setHasMore(false);
        }
        $cursor = $storage->find($collection, $expression, $queryOptions);
        $statementResult->setCursor($cursor);

        return $statementResult;
    }
}
