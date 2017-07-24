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

namespace API\View\V10\OAuth;

use API\View;

//use API\Document\Statement as StatementDocument; Re-do later

class AccessToken extends View
{
    public function render()
    {
        $accessTokenDocument = $this->service->getAccessTokens()[0];

        $view = [
            'token' => $accessTokenDocument->getToken(),
            'expiresAt' => (null === $accessTokenDocument->getExpiresAt()) ? null : $accessTokenDocument->getExpiresAt()->sec,
            'expiresIn' => $accessTokenDocument->expiresIn(),
            'createdAt' => (null === $accessTokenDocument->getCreatedAt()) ? null : $accessTokenDocument->getCreatedAt()->sec,
            'expired' => $accessTokenDocument->isExpired(),
            'scopes' => array_values($accessTokenDocument->scopes),
            'user' => $accessTokenDocument->user->renderSummary(),
            'client' => $accessTokenDocument->client->renderSummary(),
        ];

        return $view;
    }

    public function renderGet()
    {
        // POST is same as GET
        return $this->render();
    }

    public function renderPost()
    {
        // POST is same as GET
        return $this->render();
    }
}
