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

use API\Storage\Query\AttachmentInterface;

class Attachment extends Base implements AttachmentInterface
{
    public function storeAttachment($hash, $contentType, $timestamp = null)
    {
        $attachmentCollection = $this->getDocumentManager()->getCollection('attachments');

        $attachmentDocument = $attachmentCollection->createDocument();
        $attachmentDocument->setSha2($hash);
        $attachmentDocument->setContentType($contentType);
        if (null === $timestamp) {
            $timestamp = new MongoDate();
        }
        $attachmentDocument->setTimestamp($timestamp);
        $attachmentDocument->save();

        return $attachmentDocument;
    }

    public function fetchMetadataBySha2($sha2)
    {
        $collection = $this->getDocumentManager()->getCollection('attachments');
        $cursor = $collection->find();

        $cursor->where('sha2', $sha2);

        $document = $cursor->current();

        return $document;
    }
}
