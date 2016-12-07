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

namespace API\Util;

use DateTime;
use MongoDate;

class Date
{
    public static function dateTimeExact()
    {
        $date = DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)));

        return $date;
    }

    public static function dateStringToMongoDate($dateString)
    {
        $date = new DateTime($dateString);
        $mongoDate = self::dateTimeToMongoDate($date);

        return $mongoDate;
    }

    public static function dateTimeToMongoDate($dateTime)
    {
        $seconds = $dateTime->getTimestamp();
        $microseconds = $dateTime->format('u');
        $mongoDate = new MongoDate($seconds, $microseconds);

        return $mongoDate;
    }

    public static function dateTimeToISO8601($dateTime)
    {
        $dateTime = $dateTime->format('c');

        return $dateTime;
    }

    public static function secondsUntil($dateTime)
    {
        $seconds = $dateTime->getTimestamp();
        $currentTimestamp = time();

        $diff = $seconds - $currentTimestamp;

        if ($diff < 0) {
            return 0;
        } else {
            return $diff;
        }
    }

    public static function dateFromSeconds($seconds)
    {
        $dateTime = new DateTime(time() + $seconds);
        $output = self::dateTimeToISO8601($dateTime);

        return $output;
    }
}
