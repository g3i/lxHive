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

namespace API\Util;

class xAPI
{
    /**
     * normalizes xAPI IRI
     * @param $obj
     *
     * @return string|object|null
     */
    public static function extractUniqueIdentifier($obj)
    {
        $uniqueIdentifier = null;
        $obj = (object) $obj;

        // Fetch the identifier - otherwise we'd have to order the JSON
        if (isset($obj->mbox)) {
            $uniqueIdentifier = 'mbox';
        } elseif (isset($obj->mbox_sha1sum)) {
            $uniqueIdentifier = 'mbox_sha1sum';
        } elseif (isset($obj->openid])) {
            $uniqueIdentifier = 'openid';
        } elseif (isset($obj->account)) {
            $uniqueIdentifier = 'account';
        }

        return $uniqueIdentifier;
    }
}
