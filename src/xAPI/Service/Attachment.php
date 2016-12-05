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

namespace API\Service;

use API\Service;

class Attachment extends Service
{
    /**
     * Fetches file metadata from Mongo.
     *
     * @param string $sha2 The sha2 hash of the file
     *
     * @return \API\Document\Attachment The attachment document
     */
    public function fetchMetadataBySha2($sha2)
    {
        $collection  = $this->getDocumentManager()->getCollection('attachments');
        $cursor      = $collection->find();

        $cursor->where('sha2', $sha2);

        $document = $cursor->current();

        return $document;
    }

    /**
     * Fetches the actual file from the filesystem.
     *
     * @param string $sha2 The sha2 hash of the file
     *
     * @return string File contents
     */
    public function fetchFileBySha2($sha2)
    {
        $fsAdapter = \API\Util\Filesystem::generateAdapter($this->getSlim()->config('filesystem'));
        $contents = $fsAdapter->read($sha2);

        return $contents;
    }
}
