<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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
use API\Config;

class Authorize extends View
{
    public function renderGet($user, $client, $scopes)
    {
        $view = $this->getContainer()->get('view');
        $this->setItems(['csrfToken' => $_SESSION['csrfToken'],
                         'name' => Config::get(['name']),
                         'branding' => Config::get(['xAPI', 'oauth', 'branding']),
                         'user' => $user,
                         'client' => $client,
                         'scopes' => $scopes
                         ]);
        $response = $this->getResponse()->withHeader('Content-Type', 'text/html');
        $output = $view->render($response, 'authorize.twig', $this->getItems());

        return $output;
    }
}
