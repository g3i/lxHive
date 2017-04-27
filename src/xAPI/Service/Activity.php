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

namespace API\Service;

use API\Service;
use API\Util\Collection;

class Activity extends Service
{
    // Will be deprecated with ActivityResult class
    /**
     * Cursor.
     *
     * @var array
     */
    protected $cursor;

    // Will be deprecated with ActivityResult class
    /**
     * Is this a single activity state fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * Fetches activity profiles according to the given parameters.
     *
     * @param array $request The incoming HTTP request
     *
     * @return array An array of activityProfile objects.
     */
    public function activityGet($request)
    {
        $params = new Collection($request->get());

        $activity = $this->getStorage()->getActivityStorage()->fetchById($params->get('activityId'));

        $this->cursor = [$activity];
        $this->single = true;

        return $this;
    }

    /**
     * Gets the Cursor.
     *
     * @return array
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Sets the Cursor.
     *
     * @param array $cursor the cursor
     *
     * @return self
     */
    public function setCursor($cursor)
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Gets the Is this a single activity fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single activity fetch?.
     *
     * @param bool $single the is single
     *
     * @return self
     */
    public function setSingle($single)
    {
        $this->single = $single;

        return $this;
    }
}
