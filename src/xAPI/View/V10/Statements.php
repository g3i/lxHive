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

use API\Document\Statement as StatementDocument;

class Statements extends View
{
    public function renderGet($statementResult)
    {
        $view = [];

        $resultArray = [];
        $idArray = [];
        $format = $statementResult->getRequestedFormat();

        // This could be done better with pointers or a separate renderer or something... also, move valid format checking to Validator perhaps?
        foreach ($statementResult->getCursor() as $statementDocument) {
            $statementDocument = new StatementDocument($statementDocument);
            $idArray[] = $statementDocument->getId();
            if ($format === 'exact') {
                $resultArray[] = $statementDocument->renderExact();
            } elseif ($format === 'ids') {
                $resultArray[] = $statementDocument->renderIds();
            } elseif ($format === 'canonical') {
                $resultArray[] = $statementDocument->renderCanonical();
            }
        }

        $view['statements'] = $resultArray;
        $view['more'] = '';
        $view['totalCount'] = $statementResult->getTotalCount();

        // TODO: Abstract this away somewhere...
        if ($statementResult->getHasMore()) {
            $latestId = end($idArray);
            $latestId = $latestId->__toString();
            if ($statementResult->getSortDescending()) {
                $this->getContainer()->getUrl()->getQuery()->modify(['until_id' => $latestId]);
            } else { //Ascending
                $this->getContainer()->getUrl()->getQuery()->modify(['since_id' => $latestId]);
            }
            $view['more'] = $this->getContainer()->getUrl()->getRelativeUrl();
        }

        return $view;
    }

    public function renderGetSingle($statementResult)
    {
        $statement = $statementResult->getCursor()->current()->renderExact();

        return $statement;
    }

    public function renderPost($statementResult)
    {
        $response = [];

        $statements = $statementResult->getCursor();

        foreach ($statements as $document) {
            $response[] = $document->renderMeta();
        }

        return $response;
    }
}
