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

namespace API\View\V10;

use API\View;

//use API\Document\Statement as StatementDocument; Re-do later

class Statements extends View
{
    public function renderGet()
    {
        $view = [];

        $cursor = $this->service->getCursor();
        $format = $this->service->getFormat();
        $limit = $this->service->getLimit();
        $descending = $this->service->getDescending();
        $count = $this->service->getCount();

        $resultArray = [];
        $idArray     = [];

        // This could be done better with pointers or a separate renderer or something... also, move valid format checking to Validator perhaps?
        foreach ($cursor as $document) {
            $idArray[] = $document->getId();
            if ($format === 'exact') {
                $resultArray[] = $document->renderExact();
            } elseif ($format === 'ids') {
                $resultArray[] = $document->renderIds();
            } elseif ($format === 'canonical') {
                $resultArray[] = $document->renderCanonical();
            }
        }

        $view['statements'] = $resultArray;
        $view['more']       = '';
        $view['totalCount'] = $count;

        // TODO: Abstract this away somewhere...
        if (count($idArray) === $limit) {
            $latestId = end($idArray);
            $latestId = $latestId->__toString();
            if ($descending) {
                $this->getSlim()->url->getQuery()->modify(['until_id' => $latestId]);
            } else { //Ascending
                $this->getSlim()->url->getQuery()->modify(['since_id' => $latestId]);
            }
            array_pop($view['statements']);
            $view['more'] = $this->getSlim()->url->getRelativeUrl();
        }

        return $view;
    }

    public function renderGetSingle()
    {
        $statement = $this->service->getCursor()->current()->renderExact();

        return $statement;
    }

    public function renderPost()
    {
        $response = [];

        $statements = $this->service->getStatements();

        foreach ($statements as $document) {
            $response[] = $document->renderMeta();
        }

        return $response;
    }
}
