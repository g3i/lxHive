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

namespace API\View\V10;

use API\View;

class BaseDocument extends View
{
    public function renderGet()
    {
        $idArray     = [];

        $cursor = $this->service->getCursor();

        foreach ($cursor as $document) {
            $idArray[] = $document->getIdentifier();
        }

        return $idArray;
    }

    public function renderGetSingle()
    {
        $document = $this->service->getCursor()->current();
        $content = $document->getContent();

        $this->getSlim()->response->headers->set('ETag', '"'.$document->getHash().'"'); //Quotes required - RFC2616 3.11
        $this->getSlim()->response->headers->set('Content-Type', $document->getContentType());

        return $content;
    }
}
