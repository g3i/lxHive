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
     * @inherit
     */
    public function install()
    {
    }

    public function store($hash, $contentType, $timestamp = null)
    {
        $storage = $this->getContainer()['storage'];

        $attachmentDocument = new \API\Document\Generic();
        $attachmentDocument->setSha2($hash);
        $attachmentDocument->setContentType($contentType);
        if (null === $timestamp) {
            $timestamp = new \DateTime();
            $timestamp = Util\Date::dateTimeToMongoDate($timestamp);
        }
        $attachmentDocument->setTimestamp($timestamp);
        $storage->insertOne(self::COLLECTION_NAME, $attachmentDocument);

        return $attachmentDocument;
    }

    public function fetchMetadataBySha2($sha2)
    {
        $storage = $this->getContainer()['storage'];

        $expression = $storage->createExpression();

        $expression->where('sha2', $sha2);

        $document = $storage->findOne(self::COLLECTION_NAME, $expression);

        return $document;
    }
}
