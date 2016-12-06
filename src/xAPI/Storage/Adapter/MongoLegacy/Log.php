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

use API\Storage\Query\LogInterface;

class Log extends Base implements LogInterface
{
	public function logRequest($ip, $method, $endpoint, $timestamp)
	{
        $collection  = $this->getDocumentManager()->getCollection('logs');
        $document = $collection->createDocument();

        $document->setIp($ip);
        $document->setMethod($method);
        $document->setEndpoint($endpoint);
        $document->setTimestamp(Util\Date::dateTimeToMongoDate($timestamp));

        $document->save();

        return $document;
	}

}