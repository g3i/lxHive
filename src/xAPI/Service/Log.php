<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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
use API\Util;

class Log extends Service
{
    /**
     * Creates a log entry from the given request
     *
     * @param Slim\Http\Request $request The request
     *
     * @return \API\Document\Log The log document
     */
    public function logRequest($request)
    {
        $collection  = $this->getDocumentManager()->getCollection('logs');
        $document = $collection->createDocument();

        $document->setIp($request->getIp());
        $document->setMethod($request->getMethod());
        $document->setEndpoint($request->getPathInfo());
        $currentDate = Util\Date::dateTimeExact($currentDate);
        $document->setTimestamp(Util\Date::dateTimeToMongoDate($currentDate));

        $document->save();

        return $document;
    }
}
