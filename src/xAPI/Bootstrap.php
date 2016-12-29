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

namespace API;

use Monolog\Logger;
use Symfony\Component\Yaml\Parser as YamlParser;
use API\Resource;
use League\Url\Url;
use API\Bootstrap;
use API\Util\Set;
use API\Service\Auth\OAuth as OAuthService;
use API\Service\Auth\Basic as BasicAuthService;
use API\Service\Log as LogService;
use API\Parser\PsrRequest as PsrRequestParser;
use API\Service\Auth\Exception as AuthFailureException;
use API\Util\Versioning;
use Slim\Container;
use Slim\App;

class Bootstrap
{
    private $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function initWebContainer($container = null)
    {
        // Get file paths of project and config
        $appRoot = __DIR__.'/../';
        $yamlParser = new YamlParser();
        $filesystem = new \League\Flysystem\Filesystem(new \League\Flysystem\Adapter\Local($appRoot));

        // 0. Use settings from Config.yml
        $settings = $yamlParser->parse($filesystem->read('src/xAPI/Config/Config.yml'));

        // 1. Load more settings based on mode
        $settings = array_merge($settings, $yamlParser->parse($filesystem->read('src/xAPI/Config/Config.' . $settings['mode'] . '.yml')));

        // 2. Insert settings into container
        if ($container === null) {
            $container = new \Slim\Container(['settings' => $settings]);
        } else {
            $container['settings'] = $settings;
        }

        // 3. Insert URL object
        // TODO: Remove this soon
        $container['url'] = Url::createFromServer($_SERVER);

        $handlerConfig = $container['settings']['log']['handlers'];
        $stream = $appRoot.'/storage/logs/' . $settings['mode'] . '.' . date('Y-m-d') . '.log';
        
        if (null === $handlerConfig) {
            $handlerConfig = ['ErrorLogHandler'];
        }

        $logger = new Logger('web');

        $formatter = new \Monolog\Formatter\LineFormatter();

        // Set up logging
        if (in_array('FirePHPHandler', $handlerConfig)) {
            $handler = new \Monolog\Handler\FirePHPHandler();
            $logger->pushHandler($handler);
        }

        if (in_array('ChromePHPHandler', $handlerConfig)) {
            $handler = new \Monolog\Handler\ChromePHPHandler();
            $logger->pushHandler($handler);
        }

        if (in_array('StreamHandler', $handlerConfig)) {
            $handler = new \Monolog\Handler\StreamHandler($stream);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }

        if (in_array('ErrorLogHandler', $handlerConfig)) {
            $handler = new \Monolog\Handler\ErrorLogHandler();
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }

        $container['logger'] = $logger;

        $container['errorHandler'] = function ($container) {
            return function ($request, $response, $exception) use ($container) {
                $data = null;
                $code = $exception->getCode();
                if ($code < 100) {
                    $code = 500;
                }
                if (method_exists($exception, 'getData')) {
                    $data = $exception->getData();
                }
                $errorResource = new Error($container, $request, $response);
                $error = $errorResource->error($code, $exception->getMessage());

                return $error;
                //return $c['response']->withStatus($code)
                //                     ->withHeader('Content-Type', 'application/json')
                //                     ->write(json_encode([$e->getMessage(), $data]));
            };
        };

        // Storage setup
        $container['storage'] = function ($container) {
            $storageInUse = $container['settings']['storage']['in_use'];
            $storageClass = '\\API\\Storage\\Adapter\\'.$storageInUse.'\\'.$storageInUse;
            if (!class_exists($storageClass)) {
                throw new \InvalidArgumentException('Storage type selected in config is invalid!');
            }
            $storageAdapter = new $storageClass($container);

            return $storageAdapter;
        };

        $container['eventDispatcher'] = new \Symfony\Component\EventDispatcher\EventDispatcher();

        // Load any extensions that may exist
        $extensions = $container['settings']['extensions'];

        if ($extensions) {
            foreach ($extensions as $extension) {
                if ($extension['enabled'] === true) {
                    // Instantiate the extension class
                    $className = $extension['class_name'];
                    $extension = new $className($app);

                    // Load any xAPI event handlers added by the extension
                    $listeners = $extension->getEventListeners();
                    foreach ($listeners as $listener) {
                        $container['eventDispatcher']->addListener($listener['event'], [$extension, $listener['callable']], (isset($listener['priority']) ? $listener['priority'] : 0));
                    }

                    // Load any routes added by extension
                    $routes = $extension->getRoutes();
                    foreach ($routes as $route) {
                        $app->map($route['pattern'], [$extension, $route['callable']])->via($route['methods']);
                    }
                }
            }
        }

        // Parser
        $container['parser'] = function ($container) {
            $parser = new PsrRequestParser($container['request']);

            return $parser;
        };

        // Request logging
        $container['requestLog'] = function ($container) {
            $logService = new LogService($container);
            $logDocument = $logService->logRequest($container['request']);

            return $logDocument;
        };

        // Auth - token
        $container['auth'] = function ($container) {
            if (!$container['request']->isOptions() && !($container['request']->getUri()->getPath() === '/about')) {
                $basicAuthService = new BasicAuthService($container);
                $oAuthService = new OAuthService($container);

                $token = null;

                try {
                    $token = $oAuthService->extractToken($container['request']);
                    $container['requestLog']->addRelation('oAuthToken', $token)->save();
                } catch (AuthFailureException $e) {
                    // Ignore
                }

                try {
                    $token = $basicAuthService->extractToken($container['request']);
                    $container['requestLog']->addRelation('basicToken', $token)->save();
                } catch (AuthFailureException $e) {
                    // Ignore
                }

                if (null === $token) {
                    throw new \Exception('Credentials invalid!', Resource::STATUS_UNAUTHORIZED);
                }

                return $token;
            }
        };

        // Merge in specific Web settings
        $container['view'] = function ($c) {
            $view = new \Slim\Views\Twig(dirname(__FILE__).'/View/V10/OAuth/Templates', [
                'debug' => 'true',
                'cache' => dirname(__FILE__).'/View/V10/OAuth/Templates',
            ]);
            $twigDebug = new \Twig_Extension_Debug();
            $view->addExtension($twigDebug);

            return $view;
        };

        // Version
        $container['version'] = function ($c) {
            if ($container['request']->isOptions() || strpos(strtolower($container['request']->getRequestTarget()), '/about') === 0 || strpos(strtolower($container['request']->getRequestTarget()), '/oauth') === 0) {
                $versionString = $container['settings']['xAPI']['latest_version'];
            } else {
                $versionString = $container['request']->getHeaderLine('X-Experience-API-Version');
            }

            if (!$versionString) {
                throw new \Exception('X-Experience-API-Version header missing.', Resource::STATUS_BAD_REQUEST);
            } else {
                try {
                    $version = Versioning::fromString($versionString);
                } catch (\InvalidArgumentException $e) {
                    throw new \Exception('X-Experience-API-Version header invalid.', Resource::STATUS_BAD_REQUEST);
                }

                if (!in_array($versionString, $app->config('xAPI')['supported_versions'])) {
                    throw new \Exception('X-Experience-API-Version is not supported.', Resource::STATUS_BAD_REQUEST);
                }

                return $version;
            }
        };

        return $container;
    }

    public function bootWebAppWithContainer($container)
    {
        $app = new App($container);

        // CORS compatibility layer (Internet Explorer)
        /*$app->hook('slim.before.router', function () use ($app) {
            if ($app->request->isPost() && $app->request->get('method')) {
                $method = $app->request->get('method');
                $app->environment()['REQUEST_METHOD'] = strtoupper($method);
                mb_parse_str($app->request->getBody(), $postData);
                $parameters = new Set($postData);
                if ($parameters->has('content')) {
                    $content = $parameters->get('content');
                    $app->environment()['slim.input'] = $content;
                    $parameters->remove('content');
                } else {
                    // Content is the only valid body parameter...everything else are either headers or query parameters
                    $app->environment()['slim.input'] = '';
                }
                $app->request->headers->replace($parameters->all());
                $app->environment()['slim.request.query_hash'] = $parameters->all();
            }
        });

        // Parse version
        $app->hook('slim.before.dispatch', function () use ($app, $appRoot) {
        
        // Load Twig only if this is a request where we actually need it!
        if (strpos(strtolower($app->request->getPathInfo()), '/oauth') === 0) {
        $twigContainer = new Twig();
        $app->container->singleton('view', function () use ($twigContainer) {
            return $twigContainer;
        });
        $app->view->parserOptions['cache'] = $appRoot.'/storage/.Cache';
        }

        // Content type check
        if (($app->request->isPost() || $app->request->isPut()) && $app->request->getPathInfo() === '/statements' && !in_array($app->request->getMediaType(), ['application/json', 'multipart/mixed', 'application/x-www-form-urlencoded'])) {
        // Bad Content-Type
        throw new \Exception('Bad Content-Type.', Resource::STATUS_BAD_REQUEST);
        }*/

        $app->get('/{resource}[/[{action}[/]]]', function ($request, $response, $args) use ($container) {
            $resource = $args['resource'];
            if (isset($args['action'])) {
                $subResource = $args['action'];
            } else {
                $subResource = null;
            }
            $resource = Resource::load($container['version'], $resource, $subResource, $container, $request, $response);
            return $resource->get();
        });

        // Post
        $app->post('/{resource}[/[{action}[/]]]', function ($request, $response, $args) use ($container) {
            $resource = $args['resource'];
            if (isset($args['action'])) {
                $subResource = $args['action'];
            } else {
                $subResource = null;
            }
            $resource = Resource::load($container['version'], $resource, $subResource, $container, $request, $response);
            return $resource->post();
        });

        // Put
        $app->put('/{resource}[/[{action}[/]]]', function ($request, $response, $args) use ($container) {
            $resource = $args['resource'];
            if (isset($args['action'])) {
                $subResource = $args['action'];
            } else {
                $subResource = null;
            }
            $resource = Resource::load($container['version'], $resource, $subResource, $container, $request, $response);
            return $resource->put();
        });

        // Delete
        $app->delete('/{resource}[/[{action}[/]]]', function ($request, $response, $args) use ($container) {
            $resource = $args['resource'];
            if (isset($args['action'])) {
                $subResource = $args['action'];
            } else {
                $subResource = null;
            }
            $resource = Resource::load($container['version'], $resource, $subResource, $container, $request, $response);
            return $resource->delete();
        });

        // Options
        $app->options('/{resource}[/[{action}[/]]]', function ($request, $response, $args) use ($container) {
            $resource = $args['resource'];
            if (isset($args['action'])) {
                $subResource = $args['action'];
            } else {
                $subResource = null;
            }
            $resource = Resource::load($container['version'], $resource, $subResource, $container, $request, $response);
            return $resource->options();
        });

        return $app;
    }

    /**
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}