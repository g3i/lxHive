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

namespace API\Storage\Query;

use \MongoDB\Driver\Cursor;

class DocumentResult
{
    /**
     * Cursor that contains the result set.
     *
     * @var Traversable
     */
    protected $cursor;

    /**
     * Number of total documents that match in entire query.
     *
     * @var int
     */
    protected $totalCount;

    /**
     * Number of documents remaining in query where the current skip and limit values are at.
     *
     * @var int
     */
    protected $remainingCount;

    /**
     * The number of documents requested in this query (the maximum that can be contained in $cursor).
     *
     * @var int
     */
    protected $requestedLimit;

    /**
     * Whether this Result set definitely contains only one element.
     *
     * @var bool
     */
    protected $isSingle;

    /**
     * Whether there are more results available, taking into account the number of results being limited.
     *
     * @var bool
     */
    protected $hasMore;

    /**
     * Gets the The Cursor with the result set - must implement ArrayAccess or be an array (foreachable).
     *
     * @return Cursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Sets the The Cursor with the result set - must implement ArrayAccess or be an array (foreachable).
     *
     * @param Cursor $cursor
     *
     * @return self
     */
    public function setCursor($cursor)
    {
        $this->cursor = $cursor;

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
     * Gets the Whether this Result set definitely contains only one element.
     *
     * @return bool
     */
    public function getIsSingle()
    {
        return $this->isSingle;
    }

    /**
     * Sets the Whether this Result set definitely contains only one element.
     *
     * @param bool $isSingle the is single
     *
     * @return self
     */
    public function setIsSingle($isSingle)
    {
        $this->isSingle = $isSingle;

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

    /**
     * Unserialize cursor into PHP values.
     * @see http://php.net/manual/en/mongodb-driver-cursor.toarray.php
     *
     * @return object|array returns by default a stdClass if no other typeMap was set for cursor
     */
    public function toValues()
    {
        return $this->cursor->toArray();
    }
}
