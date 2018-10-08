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
 *
 * This file was adapted from slim.
 * License information is available at https://github.com/slimphp/Slim/blob/3.x/LICENSE.md
 *
 */

namespace API;

class Routes
{

    /**
     * @var array $routes default routes
     *
     * pattern:
     * [
     *    (string) "module":      core app module (or "extension"),
     *    (array)  "methods":     array of HTTP methods for this route
     *    (string) "description": a short description for the LRS /about view
     *    (string) "controller:   fully namespaced controller class name,
     *  ]
     */
    private static $routes = [

        // xAPI routes

        '/about' => [
            'module' => 'xAPI',
            'methods' => ['GET', 'OPTIONS'],
            'description' =>'LRS Information',
            'controller' => 'API\\Controller\\V10\\About',
        ],
        '/activities' => [
            'module' => 'xAPI',
            'methods' => ['GET', 'OPTIONS'],
            'description' =>'Activity Object Storage/Retrieval',
            'controller' => 'API\\Controller\\V10\\Activities',
        ],
        '/activities/profile' => [
            'module' => 'xAPI',
            'methods' => ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS'],
            'description' =>'Activity Profile Resource',
            'controller' => 'API\\Controller\\V10\\Activities\\Profile',
        ],
        '/activities/state' => [
            'module' => 'xAPI',
            'methods' => ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS'],
            'description' =>'State Resource',
            'controller' => 'API\\Controller\\V10\\Activities\\State',
        ],
        '/agents' => [
            'module' => 'xAPI',
            'methods' => ['GET', 'OPTIONS'],
            'description' =>'Agent Object Storage/Retrieval',
            'controller' => 'API\\Controller\\V10\\Agents',
        ],
        '/agents/profile' => [
            'module' => 'xAPI',
            'methods' => ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS'],
            'description' =>'Agent Profile Resource',
            'controller' => 'API\\Controller\\V10\\Agents\\Profile',
        ],
        '/statements' => [
            'module' => 'xAPI',
            'methods' => ['GET', 'PUT', 'POST', 'OPTIONS'],
            'description' =>'Statement Storage/Retrieval',
            'controller' => 'API\\Controller\\V10\\Statements',
        ],

        // oAuth routes

        '/oauth/authorize' => [
            'module' => 'oAuth',
            'methods' => ['GET', 'POST', 'OPTIONS'],
            'description' =>'Resource Owner Authorization',
            'controller' => 'API\\Controller\\V10\\Oauth\\Authorize',
        ],
        '/oauth/login' => [
            'module' => 'oAuth',
            'methods' => ['GET', 'POST', 'OPTIONS'],
            'description' =>'Resource Owner Login',
            'controller' => 'API\\Controller\\V10\\Oauth\\Login',
        ],
        '/oauth/token' => [
            'module' => 'oAuth',
            'methods' => ['POST', 'OPTIONS'],
            'description' =>'Token Request',
            'controller' => 'API\\Controller\\V10\\Oauth\\Token',
        ],

        // custom

        '/attachments' => [
            'module' => 'Storage',
            'methods' => ['GET', 'OPTIONS'],
            'description' =>'Attachment file retrieval',
            'controller' => 'API\\Controller\\V10\\Attachments',
        ],

        '/auth/tokens' => [
            'module' => 'Auth',
            'methods' => ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS'],
            'description' =>'Temporary BASIC token endpoint',
            'controller' => 'API\\Controller\\V10\\Auth\\Tokens',
        ],

        // root

        '/' => [
            'module' => 'Public',
            'methods' => ['GET', 'OPTIONS'],
            'description' =>'LRS Information',
            'controller' => 'API\\Controller\\V10\\Home', // temporary
        ],
    ];

    /**
     * Returns all routes
     *
     * @return array self::$routes
     */
    public function all()
    {
        return self::$routes;
    }

    /**
     * Merges an array of new routes into self::$routes.
     * The merging order ensures that extising routes are not overwritten by new routes
     *
     * @return void
     */
    public function merge(array $routes)
    {
        self::$routes = array_merge($routes, self::$routes);
    }
}
