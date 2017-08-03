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

namespace API\Extensions\ExtendedQuery;

use API\BaseTrait;
use API\Extensions\ExtensionInterface;

/**
 * Extended Query extension (Fragmented REST GET queries)
 * Main class -  handles registration and installation of extension
 */
class ExtendedQuery implements ExtensionInterface
{
    use BaseTrait;

    /**
     * @var array $routes
     */
    private $routes = [
        '/plus/statements/find' => [
            'module' => 'ExtendedQuery',
            'methods' => ['GET', 'HEAD', 'POST', 'OPTIONS'],
            'description' =>'find statements',
            'controller' => 'API\\Extensions\\ExtendedQuery\\Controller\\V10\\ExtendedQuery',
        ]
    ];

    /**
     * constructor
     * Register services
     * @param \Psr\Container\ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function about()
    {
        return [
            'name' => 'ExtendedQuery',
            'description' => 'Fragmented statement queries',
            'endpoints' => array_map(function ($route) {
                return [
                    'methods' => $route['methods']
                ];
            }, $this->routes),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
    }

    /**
     * Returns any event listeners that need to be added for this extension.
     * @return array Format: [['event' => 'statement.get', 'callable' => function(), 'priority' => 1 (optional)], [], ...]
     */
    public function getEventListeners()
    {
        return [];
    }

    /**
     * Returns any routes that need to be added for this extension.
     * @return array Format: [['pattern' => '/plus/superstatements', 'callable' => function(), 'methods' => ['GET', 'HEAD']], [], ...]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Returns any hooks that need to be added for this extension.
     * @return array Format: [['hook' => 'slim.before.router', 'callable' => function()], [], ...]
     */
    public function getHooks()
    {
        return [];
    }

    /**
     * Load controller
     * @param \Psr\Http\Message\ResponseInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \API\ControllerInterface
     */
    protected function getResource($request, $response)
    {
        $versionString = $this->getContainer()->version->generateClassNamespace();
        $resourceName = __NAMESPACE__.'\\Controller\\'.$versionString.'\\ExtendedQuery';
        $resource = new $resourceName($this->getContainer(), $request, $response);

        return $resource;
    }
}
