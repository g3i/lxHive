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

namespace API\View\V10\OAuth;

use API\View;

class Login extends View
{
    public function renderGet()
    {
        $view = $this->getContainer()->view;
        $view->setTemplatesDirectory(dirname(__FILE__).'/Templates');
        $this->set('csrfToken', $_SESSION['csrfToken']);
        $this->set('name', $this->getContainer()->config('name'));
        $this->set('branding', $this->getContainer()->config('xAPI')['oauth']['branding']);
        $output = $view->render('login.twig', $this->all());

        // Set Content-Type to html
        $this->getContainer()->response->headers->set('Content-Type', 'text/html');

        return $output;
    }
}
