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

    //TODO Brightcookie/lxHive-Internal/#108
    public function statementPost()
    {
        // TODO: Move header validation in a json-schema
        /*if ($request->getMediaType() !== 'application/json') {
            throw new \Exception('Media type specified in Content-Type header must be \'application/json\'!', Controller::STATUS_BAD_REQUEST);
        }*/

        // Validation has been completed already - everyhing is assumed to be valid
        $parameters = $this->getContainer()->get('parser')->getData()->getParameters();
        $bodyParams = $this->getContainer()->get('parser')->getData()->getPayload();

        $allParams = array_merge($parameters, $bodyParams);
        $response = $this->statementQuery($parameters);

        return $response;
    }

    /**
     * Fetches statement documents according to the given parameters.
     * @return \API\Storage\Query\StatementInterface  collection of statement documents
     */
    protected function statementQuery($parameters)
    {
        $storageClass = $this->resolveStorageClass();
        $extendedStatementStorage = new $storageClass($this->getContainer());
        $statementResult = $extendedStatementStorage->extendedQuery($parameters);

        return $statementResult;
    }

    /**
     * Multiple storage support
     * TODO may be obsolete
     * @return string class name
     */
    protected function resolveStorageClass()
    {
        $storageInUse = Config::get(['storage', 'in_use']);
        $storageClass = '\\API\\Extensions\\ExtendedQuery\\Storage\\Adapter\\'.$storageInUse.'\\ExtendedStatement';
        if (!class_exists($storageClass)) {
            throw new \InvalidArgumentException('Storage type selected in config is incompatible with ExtendedQuery extension!');
        }

        return $storageClass;
    }
}
