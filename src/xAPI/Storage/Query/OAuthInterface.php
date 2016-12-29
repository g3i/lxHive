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

interface OAuthInterface
{
    public function storeToken($expiresAt, $user, $client, array $scopes = [], $code = null);

    public function getToken($accessToken);

    public function deleteToken($accessToken);

    public function expireToken($accessToken);

    public function storeClient($name, $description, $redirectUri);

    public function getClients();

    public function getClientById($id);

    public function addScope($name, $description);

    public function getScopeByName($name);

    public function getTokenWithOneTimeCode($params);
}
