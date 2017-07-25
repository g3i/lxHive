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

class StatementResult extends DocumentResult
{
    protected $requestedFormat;

    protected $sortDescending;

    protected $sortAscending;

    protected $singleStatementRequest;

    /**
     * Gets the value of requestedFormat.
     * @return mixed
     */
    public function getRequestedFormat()
    {
        return $this->requestedFormat;
    }

    /**
     * Sets the value of requestedFormat.
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
     * @param mixed $singleStatementRequest the single statement request
     *
     * @return self
     */
    public function setSingleStatementRequest($singleStatementRequest)
    {
        $this->singleStatementRequest = $singleStatementRequest;

        return $this;
    }
}
