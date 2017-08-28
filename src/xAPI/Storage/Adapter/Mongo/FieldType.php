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

namespace API\Storage\Adapter\Mongo;

class FieldType
{
    const DOUBLE = 1;
    const STRING = 2;
    const OBJECT = 3;
    const ARRAY_TYPE = 4;
    const BINARY_DATA = 5;
    const UNDEFINED = 6; // deprecated
    const OBJECT_ID = 7;
    const BOOLEAN = 8;
    const DATE = 9;
    const NULL = 10;
    const REGULAR_EXPRESSION = 11;
    const JAVASCRIPT = 13;
    const SYMBOL = 14;
    const JAVASCRIPT_WITH_SCOPE = 15;
    const INT32 = 16;
    const TIMESTAMP = 17;
    const INT64 = 18;
    const MIN_KEY = 255;
    const MAX_KEY = 127;
}
