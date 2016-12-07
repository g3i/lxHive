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

namespace API\Storage\Adapter\MongoLegacy\Document;

use Sokil\Mongo\Document;

class Attachment extends Document
{
    protected $_data = [
        'sha2' => null,
        'content_type' => null,
        'mongo_timestamp' => null,
    ];

    public function setSha2($sha2)
    {
        $this->_data['sha2'] = $sha2;
    }

    public function getSha2()
    {
        return $this->_data['sha2'];
    }

    public function setContentType($contentType)
    {
        $this->_data['content_type'] = $contentType;
    }

    public function getContentType()
    {
        return $this->_data['content_type'];
    }

    public function setTimestamp($timestamp)
    {
        $this->_data['mongo_timestamp'] = $timestamp;
    }

    public function getTimestamp()
    {
        return $this->_data['mongo_timestamp'];
    }
}
