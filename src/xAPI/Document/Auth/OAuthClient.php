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

namespace API\Document\Auth;

use Sokil\Mongo\Document;

class OAuthClient extends Document implements \JsonSerializable
{
    protected $_data = [
        'clientId'    => null,
        'secret'      => null,
        'description' => null,
        'name'        => null,
        'redirectUri' => null,
    ];

    public function relations()
    {
        return [
            'oAuthTokens' => [self::RELATION_HAS_MANY, 'oAuthTokens', 'clientId'],
        ];
    }

    public function jsonSerialize()
    {
        return $this->_data;
    }

    public function renderSummary()
    {
        $return = [
            'name' => $this->_data['name'],
            'description' =>  $this->_data['description']
        ];

        return $return;
    }
}
