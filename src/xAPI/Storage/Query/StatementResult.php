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

namespace API\Storage\Query;

use InvalidArgumentException;
use API\Resource;

class StatementResult
{
	public $statementCursor;

	public $totalCount;

	public $remainingCount;

	public $requestedLimit;

	public $requestedFormat;

	public $sortDescending;

	public $sortAscending;

	public $singleStatementRequest;

	public $hasMore;

    /**
     * Gets the value of statementCursor.
     *
     * @return mixed
     */
    public function getStatementCursor()
    {
        return $this->statementCursor;
    }

    /**
     * Sets the value of statementCursor.
     *
     * @param mixed $statementCursor the statement cursor
     *
     * @return self
     */
    public function setStatementCursor($statementCursor)
    {
        $this->statementCursor = $statementCursor;

        return $this;
    }

    /**
     * Gets the value of totalCount.
     *
     * @return mixed
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * Sets the value of totalCount.
     *
     * @param mixed $totalCount the total count
     *
     * @return self
     */
    public function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    /**
     * Gets the value of remainingCount.
     *
     * @return mixed
     */
    public function getRemainingCount()
    {
        return $this->remainingCount;
    }

    /**
     * Sets the value of remainingCount.
     *
     * @param mixed $remainingCount the remaining count
     *
     * @return self
     */
    public function setRemainingCount($remainingCount)
    {
        $this->remainingCount = $remainingCount;

        return $this;
    }

    /**
     * Gets the value of requestedLimit.
     *
     * @return mixed
     */
    public function getRequestedLimit()
    {
        return $this->requestedLimit;
    }

    /**
     * Sets the value of requestedLimit.
     *
     * @param mixed $requestedLimit the requested limit
     *
     * @return self
     */
    public function setRequestedLimit($requestedLimit)
    {
        $this->requestedLimit = $requestedLimit;

        return $this;
    }

    /**
     * Gets the value of requestedFormat.
     *
     * @return mixed
     */
    public function getRequestedFormat()
    {
        return $this->requestedFormat;
    }

    /**
     * Sets the value of requestedFormat.
     *
     * @param mixed $requestedFormat the requested format
     *
     * @return self
     */
    public function setRequestedFormat($requestedFormat)
    {
        $this->requestedFormat = $requestedFormat;

        return $this;
    }

    /**
     * Gets the value of sortDescending.
     *
     * @return mixed
     */
    public function getSortDescending()
    {
        return $this->sortDescending;
    }

    /**
     * Sets the value of sortDescending.
     *
     * @param mixed $sortDescending the sort descending
     *
     * @return self
     */
    public function setSortDescending($sortDescending)
    {
        $this->sortDescending = $sortDescending;

        return $this;
    }

    /**
     * Gets the value of sortAscending.
     *
     * @return mixed
     */
    public function getSortAscending()
    {
        return $this->sortAscending;
    }

    /**
     * Sets the value of sortAscending.
     *
     * @param mixed $sortAscending the sort ascending
     *
     * @return self
     */
    public function setSortAscending($sortAscending)
    {
        $this->sortAscending = $sortAscending;

        return $this;
    }

    /**
     * Gets the value of singleStatementRequest.
     *
     * @return mixed
     */
    public function getSingleStatementRequest()
    {
        return $this->singleStatementRequest;
    }

    /**
     * Sets the value of singleStatementRequest.
     *
     * @param mixed $singleStatementRequest the single statement request
     *
     * @return self
     */
    public function setSingleStatementRequest($singleStatementRequest)
    {
        $this->singleStatementRequest = $singleStatementRequest;

        return $this;
    }

    /**
     * Gets the value of hasMore.
     *
     * @return mixed
     */
    public function getHasMore()
    {
        return $this->hasMore;
    }

    /**
     * Sets the value of hasMore.
     *
     * @param mixed $hasMore the has more
     *
     * @return self
     */
    public function setHasMore($hasMore)
    {
        $this->hasMore = $hasMore;

        return $this;
    }
}