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

namespace API\Extensions;

interface ExtensionInterface
{
    /**
     * Returns any event listeners that need to be added for this extension.
     *
     * @return array Format: [['event' => 'statement.get', 'callable' => function(), 'priority' => 1 (optional)], [], ...]
     */
    public function getEventListeners();

    /**
     * Returns any routes that need to be added for this extension.
     *
     * @return array Format: [['pattern' => '/plus/superstatements', 'callable' => function(), 'methods' => ['GET', 'HEAD']], [], ...]
     */
    public function getRoutes();

    /**
     * Install extension, apply models and configurations
     *
     * @return void
     * @throws \API\Storage\AdapterException
     * @throws \MongoDB\Driver\Exception\Exception
    */
    public function install();

    /**
     * Provide information for /about endpoint
     * @see https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Communication.md#aboutresource
     * @return array
    */
    public function about();
}
