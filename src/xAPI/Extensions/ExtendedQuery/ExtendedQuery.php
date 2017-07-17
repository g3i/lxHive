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

    //private $routes = [
    //    '/plus/statements/find' => [
    //        'methods' => [
    //            'OPTIONS'   => [ 'callable' => 'handleOptionsRoute'],
    //            'GET'       => [ 'callable' => 'handleGetRoute'],
    //            'HEAD'      => [ 'callable' => 'handleGetRoute'],
    //            'POST'      => [ 'callable' => 'handlePostRoute'],
    //        ],
    //    ],
    //];

    private $routes = [
        ['pattern' => '/plus/statements/find', 'callable' => 'handleGetRoute', 'methods' => ['GET', 'HEAD']],
        ['pattern' => '/plus/statements/find', 'callable' => 'handlePostRoute', 'methods' => ['POST']],
        ['pattern' => '/plus/statements/find', 'callable' => 'handleOptionsRoute', 'methods' => ['OPTIONS']],
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

        $routes = [];
        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];
            $methods = (isset($routes[$pattern])) ? array_merge($routes[$pattern], $route['methods']) : $route['methods'];
            $routes[$pattern] = $methods;
        }

        return [
            'name' => 'ExtendedQuery',
            'description' => 'Fragmented statement queries',
            'endpoints' => $routes,
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

    /**
     * Process GET request
     * @param \Psr\Http\Message\ResponseInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args collection of extra arguments
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handleGetRoute($request, $response, $args)
    {
        $response = $this->getResource($request, $response)->get();

        return $response;
    }

    /**
     * Process POST request
     * @param \Psr\Http\Message\ResponseInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args collection of extra arguments
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handlePostRoute($request, $response, $args)
    {
        $response = $this->getResource($request, $response)->post();

        return $response;
    }

    /**
     * Process OPTIONS request
     * @param \Psr\Http\Message\ResponseInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args collection of extra arguments
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handleOptionsRoute($request, $response, $args)
    {
        $response = $this->getResource($request, $response)->options();

        return $response;
    }
}
