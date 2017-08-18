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
use API\Storage\Query\AttachmentInterface;

use API\Util;
use API\Storage\Provider;

class Attachment extends Provider implements AttachmentInterface, SchemaInterface
{
    const COLLECTION_NAME = 'attachments';

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
        // TODO 0.11.x: Enable attachment indexing - planned for
        // This will require checksum matching to check for existing attachments
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
    public function store($sha2, $contentType, $timestamp = null)
    {
        $storage = $this->getContainer()->get('storage');

        $attachmentDocument = new \API\Document\Generic();
        $attachmentDocument->setSha2($sha2);
        $attachmentDocument->setContentType($contentType);
        if (null === $timestamp) {
            $timestamp = new \DateTime();
            $timestamp = Util\Date::dateTimeToMongoDate($timestamp);
        }
        $attachmentDocument->setTimestamp($timestamp);
        $storage->insertOne(self::COLLECTION_NAME, $attachmentDocument);

        return $attachmentDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchMetadataBySha2($sha2)
    {
        $storage = $this->getContainer()->get('storage');

        $expression = $storage->createExpression();
        $expression->where('sha2', $sha2);
        $document = $storage->findOne(self::COLLECTION_NAME, $expression);

        return $document;
    }
}
