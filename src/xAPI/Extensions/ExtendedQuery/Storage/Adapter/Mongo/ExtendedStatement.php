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

namespace API\Extensions\ExtendedQuery\Storage\Adapter\Mongo;

use API\Extensions\ExtendedQuery\Storage\Query\ExtendedStatementInterface;
use API\Storage\Provider;
use API\Storage\Query\StatementResult;
use API\Controller;
use API\Config;

use API\Extensions\ExtensionException as Exception;

/**
 * Mongo Adaptor for this extension
 */
class ExtendedStatement extends Provider implements ExtendedStatementInterface
{
    /**
     * Query statements collection
     * @param  array $parameters hashmap of GET params
     * @return \API\Storage\Query\StatementInterface collection of statement documents
     */
    public function extendedQuery($parameters)
    {
        $storage = $this->getContainer()->get('storage');
        $collection = 'statements';

        $queryOptions = [];

        // New StatementResult for non-single statement queries
        $statementResult = new StatementResult();

        // Blank expression
        $expression = $storage->createExpression();

        // Merge in query
        if (isset($parameters['query'])) {
            $query = $parameters['query'];

            if (is_string($query)) {
                $query = json_decode($query, true);
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in query param.', Controller::STATUS_BAD_REQUEST);
            }

            $expression->fromArray($query);
        }

        // Add projection
        if (isset($parameters['projection'])) {
            $fields = $parameters['projection'];

            if (is_string($fields)) {
                $fields = json_decode($fields, true);
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in projection param.', Controller::STATUS_BAD_REQUEST);
            }

            foreach ($fields as $field => $value) {
                if (strpos($field, 'statement.') !== 0) {
                    throw new Exception('Invalid projection parameters!.', Controller::STATUS_BAD_REQUEST);
                }
            }
            $fields = ['_id' => 1] + $fields;
            $queryOptions['projection'] = $fields;
        } else {
            $queryOptions['projection'] = ['_id' => 1, 'statement' => 1];
        }

        // Count before paginating
        $count = $storage->count($collection, $expression, $queryOptions);
        $statementResult->setTotalCount($count);

        // Handle pagination
        if (isset($parameters['since_id'])) {
            $id = new \MongoDB\BSON\ObjectID($parameters->get('since_id'));
            $expression->whereGreater('_id', $id);
        }

        if (isset($parameters['until_id'])) {
            $id = new \MongoDB\BSON\ObjectID($parameters->get('until_id'));
            $expression->whereLess('_id', $id);
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

        if (isset($parameters['limit']) && $parameters['limit'] < Config::get(['xAPI', 'statement_get_limit']) && $parameters['limit'] > 0) {
            $limit = $parameters['limit'];
        } else {
            $limit = Config::get(['xAPI', 'statement_get_limit']);
        }

        // Remaining includes the current page!
        $statementResult->setRemainingCount($storage->count($collection, $expression, $queryOptions));

        if ($statementResult->getRemainingCount() > $limit) {
            $statementResult->setHasMore(true);
        } else {
            $statementResult->setHasMore(false);
        }

        $queryOptions['limit'] = (int)$limit;

        $cursor = $storage->find($collection, $expression, $queryOptions);
        $statementResult->setCursor($cursor);

        return $statementResult;
    }
}
