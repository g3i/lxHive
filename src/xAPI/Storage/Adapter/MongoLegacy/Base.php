<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2016 Brightcookie Pty Ltd
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

namespace API\Storage\Adapter\MongoLegacy;

use API\Storage\Adapter\Base as StorageBase;
use Sokil\Mongo\Client;

class Base extends StorageBase
{
    protected $documentManager;
    /**
     * Constructor.
     *
     * @param \Slim\Slim $slim Slim framework - in future DI container
     */
    public function __construct($container)
    {
        parent::__construct($container);

        $client = new Client($this->getContainer()->config('storage')['MongoLegacy']['host_uri']);
        $client->map([
            $this->getContainer()->config('storage')['MongoLegacy']['db_name'] => '\API\Storage\Adapter\MongoLegacy\Collection',
        ]);
        $client->useDatabase($this->getContainer()->config('storage')['MongoLegacy']['db_name']);

        $this->setDocumentManager($client);
    }

    /**
     * Gets the value of documentManager.
     *
     * @return mixed
     */
    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    /**
     * Sets the value of documentManager.
     *
     * @param mixed $documentManager the document manager
     *
     * @return self
     */
    protected function setDocumentManager($documentManager)
    {
        $this->documentManager = $documentManager;

        return $this;
    }
}
