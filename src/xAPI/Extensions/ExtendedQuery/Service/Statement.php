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

namespace API\Extensions\ExtendedQuery\Service;

use API\Service;
use API\Controller;
use Slim\Helper\Set;
use API\Config;

use API\Extensions\ExtensionException as Exception;

/**
 * Statements Service
 */
class Statement extends Service
{
    /**
     * Fetches statement documents according to the given parameters.
     * @return \API\Storage\Query\StatementInterface  collection of statement documents
     */
    public function statementGet()
    {
        $parameters = $this->getContainer()->get('parser')->getData()->getParameters();

        $response = $this->statementQuery($parameters);

        return $response;
    }

    public function statementPost()
    {
        // Validation has been completed already - everyhing is assumed to be valid
        $parameters = $this->getContainer()->get('parser')->getData()->getParameters();
        $bodyParams = $this->getContainer()->get('parser')->getData()->getPayload();

        $allParams = (object)array_merge((array)$parameters, (array)$bodyParams);
        $response = $this->statementQuery($parameters);

        return $response;
    }

    /**
     * Fetches statement documents according to the given parameters.
     * @return \API\Storage\Query\StatementInterface  collection of statement documents
     */
    protected function statementQuery($parameters)
    {
        $parameters = (object)$parameters;
        $storageClass = $this->resolveStorageClass();
        $extendedStatementStorage = new $storageClass($this->getContainer());

        // Parse parameters
        if (isset($parameters->query) && is_string($parameters->query)) {
            $parameters->query = json_decode($parameters->query);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in query param.', Controller::STATUS_BAD_REQUEST);
            }
        }

        if (isset($parameters->projection) && is_string($parameters->projection)) {
            $parameters->projection = json_decode($parameters->projection);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in projection param.', Controller::STATUS_BAD_REQUEST);
            }

            foreach ($parameters->projection as $field => $value) {
                if (strpos($field, 'statement.') !== 0) {
                    throw new Exception('Invalid projection parameters!.', Controller::STATUS_BAD_REQUEST);
                }
            }
        }

        $statementResult = $extendedStatementStorage->extendedQuery($parameters);

        return $statementResult;
    }

    /**
     * Multiple storage support
     *
     * @return string class name
     */
    protected function resolveStorageClass()
    {
        $storageInUse = Config::get(['storage', 'in_use']);
        $storageClass = '\\API\\Extensions\\ExtendedQuery\\Storage\\Adapter\\'.$storageInUse.'\\ExtendedStatement';
        if (!class_exists($storageClass)) {
            throw new Exception('Storage type selected in config is incompatible with ExtendedQuery extension!', Controller::STATUS_INTERNAL_SERVER_ERROR);
        }

        return $storageClass;
    }
}
