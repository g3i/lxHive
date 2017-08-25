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
     * @param object $obj IRI
     *
     * @return string|null
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
        } elseif (isset($obj->openid)) {
            $uniqueIdentifier = 'openid';
        } elseif (isset($obj->account)) {
            $uniqueIdentifier = 'account';
        }

        return $uniqueIdentifier;
    }

    /**
     * Extracts xAPI IRI objectType property. Inspects property for allowed values
     * This function does not validate other IRI properties in relation to objectType
     * @param object $obj IRI
     *
     * @return string|null
     */
    public static function extractIriObjectType($obj)
    {
        $obj = (object) $obj;

        if (!isset($obj->objectType)) {
            return 'Agent';
        }

        // Case sensititive!
        if (isset($obj->objectType)) {
            if($obj->objectType == 'Agent') {
                return 'Agent';
            }
            if($obj->objectType == 'Group') {
                return 'Group';
            }
        }
        // Invalid or falsy objectType values
        return null;
    }

    /**
     * Normalizes upercase and legacy UUID patterns (issue#76)
     * @param string $uuid
     *
     * @return string normalized uuid (ready for \Mongo\ObjectId::__constuct())
     */
    public static function normalizeUuid($uuid)
    {
        return strtolower(str_replace(['urn:', 'uuid:', '{', '}'], '', $uuid));
    }
}
