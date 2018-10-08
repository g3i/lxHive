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
use API\Storage\Query\OAuthClientsInterface;

use API\Util;
use API\Storage\Provider;

class OAuthClients extends Provider implements OAuthClientsInterface, SchemaInterface
{
    const COLLECTION_NAME = 'oAuthClients';


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
    private $indexes = [];

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
    public function getClientById($id)
    {
        $storage = $this->getContainer()->get('storage');
        $expression = $storage->createExpression();

        $expression->where('clientId', $id);
        $clientDocument = $storage->findOne(self::COLLECTION_NAME, $expression);

        return $clientDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function getClients()
    {
        $storage = $this->getContainer()->get('storage');

        $cursor = $storage->find(self::COLLECTION_NAME);
        $documentResult = new \API\Storage\Query\DocumentResult();
        $documentResult->setCursor($cursor);

        return $documentResult;
    }

    /**
     * {@inheritDoc}
     */
    public function addClient($name, $description, $redirectUri)
    {
        $storage = $this->getContainer()->get('storage');

        // Set up the Client to be saved
        $clientDocument = new \API\Document\Generic();

        $clientDocument->setName($name);
        $clientDocument->setDescription($description);
        $clientDocument->setRedirectUri($redirectUri);

        $clientId = Util\OAuth::generateToken();
        $clientDocument->setClientId($clientId);

        $secret = Util\OAuth::generateToken();
        $clientDocument->setSecret($secret);

        $storage->insertOne(self::COLLECTION_NAME, $clientDocument);

        return $clientDocument;
    }
}
