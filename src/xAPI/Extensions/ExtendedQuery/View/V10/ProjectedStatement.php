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

namespace API\Extensions\ExtendedQuery\View\V10;

use API\View;

class ProjectedStatement extends View
{
    public function render($statementResult)
    {
        $view = [];

        if (!is_array($statementResult) && $statementResult instanceof Traversable) {
            $resultArray = iterator_to_array($statementResult->getCursor());
        }

        $view['statements'] = [];
        $view['more']       = '';
        $view['totalCount'] = $count;

        if ($statementResult->getHasMore()) {
            $latestId = end($resultArray)->getId();
            $latestId = $latestId->__toString();
            if ($descending) {
                $this->getSlim()->url->getQuery()->modify(['until_id' => $latestId]);
            } else { //Ascending
                $this->getSlim()->url->getQuery()->modify(['since_id' => $latestId]);
            }
            // Removed, since hack was also removed
            //array_pop($resultArray);
            $view['more'] = $this->getSlim()->url->getRelativeUrl();
        }

        foreach ($resultArray as $result) {
            if (!is_array($result) && $result instanceof Traversable) {
                $result = $result->toArray();
            }
            unset($result['_id']);
            if (isset($result['statement'])) {
                $result = $result['statement'];
            }
        }

        $view['statements'] = array_values($resultArray);

        return $view;
    }
}
