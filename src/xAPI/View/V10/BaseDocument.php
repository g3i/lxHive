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

namespace API\View\V10;

use API\View;

abstract class BaseDocument extends View
{
    public function renderGet($documentResult)
    {
        $idArray = [];

        $cursor = $documentResult->getCursor();

        foreach ($cursor as $document) {
            $idArray[] = $document->{static::IDENTIFIER};
        }

        return $idArray;
    }

    public function renderGetSingle($documentResult)
    {
        $document = current($documentResult->getCursor()->toArray());
        $document = new \API\Document\Generic($document);
        $content = $document->getContent();

        // Write content
        $newResponse = $this->getResponse()->withHeader('ETag', '"'.$document->getHash().'"')
                                           ->withHeader('Content-Type', $document->getContentType());
        // Write body
        $body = $newResponse->getBody();
        $body->write($content);

        return $newResponse;
    }
}
