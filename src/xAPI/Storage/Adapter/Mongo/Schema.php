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

use MongoDB\Driver\Exception\Exception as MongoException;

use API\BaseTrait;
use API\Storage\AdapterException;
use API\Storage\SchemaInterface;
use API\Storage\Adapter\Mongo as Mongo;

class Schema implements SchemaInterface
{
    use BaseTrait;

    /**
     * Constructor.
     *
     * @param PSR-11 Container
     */
    public function __construct($container)
    {
        $this->setContainer($container);
    }

    /*
     * Maps collection names to classnames
     *
     * @return void;
     */
    public function mapCollections()
    {
        return [
             Activity::COLLECTION_NAME          => __NAMESPACE__ . '\\Activity',
             ActivityProfile::COLLECTION_NAME   => __NAMESPACE__ . '\\ActivityProfile',
             ActivityState::COLLECTION_NAME     => __NAMESPACE__ . '\\ActivityState',
             AgentProfile::COLLECTION_NAME      => __NAMESPACE__ . '\\AgentProfile',
             Attachment::COLLECTION_NAME        => __NAMESPACE__ . '\\Attachment',
             BasicAuth::COLLECTION_NAME         => __NAMESPACE__ . '\\BasicAuth',
             Log::COLLECTION_NAME               => __NAMESPACE__ . '\\Log',
             OAuth::COLLECTION_NAME             => __NAMESPACE__ . '\\OAuth',
             OAuthClients::COLLECTION_NAME      => __NAMESPACE__ . '\\OAuthClients',
             Statement::COLLECTION_NAME         => __NAMESPACE__ . '\\Statement',
             User::COLLECTION_NAME              => __NAMESPACE__ . '\\User',
        ];
    }

    /*
     * {@inheritDoc}
     */
    public function install()
    {
        $collections = $this->mapCollections();
        $container = $this->getContainer();

        // Verify DB compatibility
        $mongo = new Mongo($container);
        $mongo->verifyDatabaseVersion();

        foreach ($collections as $collection => $className) {
            $instance = new $className($container);
            try {
                $instance->install();
            } catch (MongoException $e) {
                throw new AdapterException('Unable to install collection "' .$collection. '": '.$e->getMessage());
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexes()
    {
        $indexes = [];
        $collections = $this->mapCollections();
        $container = $this->getContainer();

        foreach ($collections as $collection => $className) {
            $instance = new $className($container);
            $indexes[$collection] = $instance->getIndexes();
        }

        return $indexes;
    }
}
