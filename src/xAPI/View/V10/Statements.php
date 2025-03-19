<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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
        $view['more'] = $this->renderMore($statementResult, $idArray);
        $view['totalCount'] = $statementResult->getTotalCount();

        // TODO: Abstract this away somewhere...


        return $view;
    }

    private function renderMore($statementResult, $idArray)
    {
        if ($statementResult->getHasMore() && !empty($idArray)) {//TODO @sraka1 temporary fix for https://github.com/g3i/lxHive-Internal/issues/229
            $latestId = end($idArray);
            $latestId = (string) $latestId;

            $uri = $this->getContainer()->get('request')->getUri();

            if ($statementResult->getSortDescending()) {
                $uri = $uri->withQuery('until_id='.$latestId);
            } else { //Ascending
                $uri = $uri->withQuery('since_id='.$latestId);
            }

            $relativeUri = $uri->withScheme('')->withHost('')->withUserInfo('')->withPort(null);
            return (string) $relativeUri;
        } else {
            return '';
        }
    }

    public function renderGetSingle($statementResult)
    {
        $statement = current($statementResult->getCursor());
        $statementDocument = new StatementDocument($statement);
        $view = $statementDocument->renderExact();

        return $view;
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
