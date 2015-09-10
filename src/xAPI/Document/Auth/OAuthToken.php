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

use Slim\Slim;

class OAuthToken extends AbstractToken
{
    protected $_data = [
        'userId'           => null,
        'clientId'         => null,
        'token'            => null,
        'expiresAt'        => null,
        'createdAt'        => null,
        'code'             => null,
    ];

    public function relations()
    {
        return [
            'user'   => [self::RELATION_BELONGS, 'users', 'userId'],
            'client' => [self::RELATION_BELONGS, 'oAuthClients', 'clientId'],
            'scopes' => [self::RELATION_MANY_MANY, 'authScopes', 'scopeIds', true],
            'logs' => [self::RELATION_HAS_MANY, 'logs', 'oAuthTokenId']
        ];
    }

    public function generateAuthority()
    {
        $slim = Slim::getInstance();
        $url = $slim->url;
        $host = $url->getHost()->__toString();
        $authority = [
            'objectType' => 'Group',
            'member' => [
                [
                    'account' => [
                        'homePage' => $host.'/oauth/token',
                        'name' => 'oauth_consumer_'.$this->getClientId(),
                    ],
                ],
                [
                    'mbox' => 'mailto:'.$this->user->getEmail(),
                ],
            ],
        ];

        return $authority;
    }
}
