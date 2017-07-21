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
 *
 * This file was adapted from sokil/php-mongo.
 * License information is available at https://github.com/sokil/php-mongo/blob/master/LICENSE
 *
 */

namespace API\Storage;

interface SchemaInterface
{
    /**
     * Install hook for collection
     * @return void
     * @throws \Exception
     */
    public function install();

    /**
     * Gets indexes configuration array
     *
     * @return array
     */
    public function getIndexes();
}
