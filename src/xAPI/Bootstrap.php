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

namespace API;

use Monolog\Logger;
use Slim\App as SlimApp;
use Slim\DefaultServicesProvider;

use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Extension\DebugExtension as TwigDebugExtension;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToReadFile as FileNotFoundException;

use API\Config;
use API\Controller;
use API\Controller\Error;
use API\Parser\RequestParser;

use API\Util\Collection;
use API\Util\Versioning;
use API\Console\Application as CliApp;

use API\Service\Log as LogService;
use API\Service\Auth as AuthService;
use API\Service\Auth\OAuth as OAuthService;
use API\Service\Auth\Basic as BasicAuthService;
use API\Service\Auth\Exception as AuthFailureException;

/**
 * Bootstrap lxHive
 *
 * Bootstrap routines fall into two steps:
 *      1 factory (initialization)
 *      2 boot application
 * Example for booting a Web App:
 *      $bootstrap = \API\Bootstrap::factory(\API\Bootstrap::Web);
 *      $app = $bootstrap->bootWebApp();
 * An app can only be bootstraped once, the only Exception being mode Bootstap::Testing
 */
class Bootstrap
{
    /**
     * @var string VERSION phpversion() parable application version  string(SemVer), synchs with Config.yml version
     */
    const VERSION = '0.10.0';

    /**
     * @var Bootstrap Mode
     */
    const None    = 0;
    const Web     = 1;
    const Console = 2;
    const Testing = 3;
    const Config  = 4;

    private static $containerInstance = null;
    private static $containerInstantiated = false;

    private static $mode = 0;

    /**
     * constructor
     * Sets bootstrap mode
     * @param  int $mode Bootstrap mode constant
     */
    private function __construct($mode)
    {
        self::$mode = ($mode) ? $mode : self::None; // casting [0, null, false] to self::None
    }

    /**
     * Factory for container contained within bootstrap, which is a base for various initializations
     *
     * | Mode               | config   | services  | routes    | extensions | can reboot?   | scope                 |
     * |--------------------|----------|-----------|-----------|------------|---------------| ----------------------|
     * | Bootstrap::None    | -        | -         | -         | -          | yes           | n/a                   |
     * | Bootstrap::Config  | x        | -         | -         | -          | yes           | load config only      |
     * | Bootstrap::Testing | x        | x         | -         | -          | yes           | unit tests            |
     * | Bootstrap::Console | x        | x         | -         | -          | no            | admin console         |
     * | Bootstrap::Web     | x        | x         | x         | x          | no            | default: run web app  |
     *
     * @param  int $mode Bootstrap mode constant
     * @param array config merge/overwrite values (test mode only)
     * @return void
     * @throw AppInitException
     */
    public static function factory($mode, $testConfig=[])
    {
        if (self::$containerInstantiated) {
            // modes test and none (admin,etc) shall pass
            if (
                   $mode !== self::Testing
                && $mode !== self::None
                && $mode !== self::Config
            ) {
                throw new AppInitException('Bootstrap: You can only instantiate the Bootstrapper once!');
            }
        }

        $bootstrap = new self($mode);

        switch (self::$mode) {
            case self::Web: {
                $bootstrap->initConfig();
                $container = $bootstrap->initWebContainer();
                self::$containerInstance = $container;
                self::$containerInstantiated = true;
                return $bootstrap;
                break;
            }

            case self::Console: {
                $bootstrap->initConfig();
                $container = $bootstrap->initCliContainer();
                self::$containerInstance = $container;
                self::$containerInstantiated = true;
                return $bootstrap;
                break;
            }

            case self::Testing: {
                $bootstrap->initConfig($testConfig);
                $container = $bootstrap->initGenericContainer();
                self::$containerInstance = $container;
                self::$containerInstantiated = true;
                return $bootstrap;
                break;
            }

            case self::Config: {
                $bootstrap->initConfig();
                return $bootstrap;
                break;
            }

            case self::None: {
                return $bootstrap;
                break;
            }

            default: {
                throw new AppInitException('Bootstrap: You must provide a valid mode when calling the Boostrapper factory!');
            }
        }
    }

    /**
     * Reset Bootstrap
     * @ignore do not compile to docs
     * @return void
     * @throw AppInitException if self::mode does not allow reboot
     */
    public static function reset()
    {
        if (
               self::$mode === self::Testing
            || self::$mode === self::None
            || self::$mode === self::Config
        ) {
            self::$mode = self::None;
            self::$containerInstantiated = false;
            self::$containerInstance = false;
            Config::reset();
            return;
        }

        throw new AppInitException('Bootstrap: reset not allowed in this mode (' . self::$mode . ')');
    }

    /**
     * Returns the current bootstrap mode
     * See mode constants.
     * Check if the Bootstrap was initialized
     *      if(!Bootstrap::mode()) {
     *          ...
     *      }
     * @return int current mode
     */
    public static function mode()
    {
        return self::$mode;
    }

    /**
     * Get service container
     * @return \Psr\Container\ContainerInterface|null
     */
    public static function getContainer()
    {
        return self::$containerInstance;
    }

    /**
     * Initialize default configuration and load services
     * @param array config merge/overwrite values (test mode only)
     *
     * @return \Psr\Container\ContainerInterface service container
     * @throws AppInitException if self::$mode > self::None
     */
    public function initConfig($testConfig=[])
    {
        // Defaults
        $appRoot = realpath(__DIR__.'/../../');
        $defaults = [
            'appRoot' => $appRoot,
            'publicRoot' => $appRoot.'/public',
        ];

        Config::factory($defaults);

        $filesystem = new Filesystem(new LocalFilesystemAdapter($appRoot));
        $yamlParser = new YamlParser();

        try {
            $contents = $filesystem->read('src/xAPI/Config/Config.yml');
            $config = $yamlParser->parse($contents);
        } catch (FileNotFoundException $e) {
            if (self::$mode === self::None) {
                return;
            } else {
                // throw AppInit exception
                throw new AppInitException('Cannot load configuration: '.$e->getMessage());
            }
        }

        try {
            $contents = $filesystem->read('src/xAPI/Config/Config.' . $config['mode'] . '.yml');
            $config = array_merge($config, $yamlParser->parse($contents));
        } catch (FileNotFoundException $e) {
            // Ignore exception
        }

        // ad-hoc db for unit test @see phpunit.xml
        // #238 allow overwriting config properties inside unittests
        if (defined('LXHIVE_UNITTEST')) {
            $config['storage']['Mongo']['db_name'] = 'LXHIVE_UNITTEST';
            if ($testConfig) {
                $config = array_merge($config, $testConfig);
            }
        }

        Config::merge($config);
    }

    /**
     * Initialize default configuration and load services
     * @return \Psr\Container\ContainerInterface service container
     * @throws AppInitException
     */
    public function initGenericContainer()
    {
        // 2. Create default container
        if (self::$mode === self::Web) {
            $container = new \Slim\Container();
        } else {
            $container = new Container();
        }

        // 3. Storage setup
        $container['storage'] = function ($container) {
            $storageInUse = Config::get(['storage', 'in_use']);
            $storageClass = '\\API\\Storage\\Adapter\\'.$storageInUse;
            if (!class_exists($storageClass)) {
                throw new AppInitException('Bootstrap: Storage type selected in config is invalid!');
            }
            $storageAdapter = new $storageClass($container);

            return $storageAdapter;
        };

        return $container;
    }

    /**
     * Initialize  web mode configuration and load services
     * @return \Psr\Container\ContainerInterface service container
     * @throws AppInitException
     * @throws HttpException on authentication denied or invalid request
     */
    public function initWebContainer($container = null)
    {
        $appRoot = realpath(__DIR__.'/../../');
        $container = $this->initGenericContainer($container);

        // 4. Set up Slim services
        /*
           * Slim\App expects a container that implements Psr\Container\ContainerInterface
           * with these service keys configured and ready for use:
           *
           *  `settings`          an array or instance of \ArrayAccess
           *  `environment`       an instance of \Slim\Http\Environment
           *  `request`           an instance of \Psr\Http\Message\ServerRequestInterface
           *  `response`          an instance of \Psr\Http\Message\ResponseInterface
           *  `router`            an instance of \Slim\Interfaces\RouterInterface
           *  `foundHandler`      an instance of \Slim\Interfaces\InvocationStrategyInterface
           *  `errorHandler`      a callable with the signature: function($request, $response, $exception)
           *  `notFoundHandler`   a callable with the signature: function($request, $response)
           *  `notAllowedHandler` a callable with the signature: function($request, $response, $allowedHttpMethods)
           *  `callableResolver`  an instance of \Slim\Interfaces\CallableResolverInterface
        */
        $slimDefaultServiceProvider = new DefaultServicesProvider();
        $slimDefaultServiceProvider->register($container);

        $debug = Config::get('debug', false);
        if ($container->has('settings')) {
            $container['settings']['displayErrorDetails'] = $debug;
        }

        $handlerConfig = Config::get(['log', 'handlers'], ['ErrorLogHandler']);
        $defaultLevel = Config::get(['log', 'level'], Logger::DEBUG);
        $defaultLog = $appRoot.'/storage/logs/' . Config::get('mode') . '.' . date('Y-m-d') . '.log';

        $logger = new Logger('web');
        $formatter = new \Monolog\Formatter\LineFormatter("[%datetime%][%channel%][%level_name%]: %message% %context% %extra%\n", null, true, true);

        // Set up logging
        if (in_array('FirePHPHandler', $handlerConfig)) {
            $level = Config::get(['log', 'FirePHPHandler', 'level'], $defaultLevel);
            $handler = new \Monolog\Handler\FirePHPHandler($level);
            $logger->pushHandler($handler);
        }

        if (in_array('ChromePHPHandler', $handlerConfig)) {
            $level = Config::get(['log', 'ChromePHPHandler', 'level'], $defaultLevel);
            $handler = new \Monolog\Handler\ChromePHPHandler($level);
            $logger->pushHandler($handler);
        }

        if (in_array('StreamHandler', $handlerConfig)) {
            $level = Config::get(['log', 'StreamHandler', 'level'], $defaultLevel);
            $stream = Config::get(['log', 'StreamHandler', 'stream'], $defaultLog);

            $handler = new \Monolog\Handler\StreamHandler($stream, $level);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }

        if (in_array('ErrorLogHandler', $handlerConfig)) {
            $errorLog = Config::get(['log', 'ErrorLogHandler', 'error_log']);
            if ($errorLog) {
                // @see https://www.php.net/manual/en/errorfunc.configuration.php#ini.error-log
                ini_set('log_errors', 1); // not set in ErrorLogHandler
                ini_set('error_log', ($errorLog == 'default') ? $defaultLog : $errorLog);
            }

            $level = Config::get(['log', 'ErrorLogHandler', 'level'], $defaultLevel);
            $handler = new \Monolog\Handler\ErrorLogHandler(\Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, $level);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }

        $container['logger'] = $logger;

        $container['errorHandler'] = function ($container) {
            return function ($request, $response, $exception) use ($container) {
                $data = [];
                $code = $exception->getCode();
                $message = $exception->getMessage();
                if ($code < 100) {
                    $code = 500;
                }

                // catch MongoDB exceptions, adjust codes AND prevent exception messages giving away connection details
                if (is_subclass_of($exception, '\MongoDB\Driver\Exception\Exception')) {
                    $code = 500;
                    if(Config::get('mode', 'production') !== 'development' ){
                        $message = 'Database error: ['.$exception->getCode().'], '.get_class($exception);
                    }
                }

                if (method_exists($exception, 'getData')) {
                    $data = $exception->getData();
                }
                $errorResource = new Error($container, $request, $response);
                $error = $errorResource->error($code, $message, $data);

                return $error;
                //return $c['response']->withStatus($code)
                //                     ->withHeader('Content-Type', 'application/json')
                //                     ->write(json_encode([$e->getMessage(), $data]));
            };
        };

        $container['eventDispatcher'] = new EventDispatcher();

        // Parser
        $container['parser'] = function ($container) {
            $parser = new RequestParser($container['request']);

            return $parser;
        };

        // Request logging
        $container['requestLog'] = function ($container) {
            $logService = new LogService($container);
            $logDocument = $logService->logRequest($container['request']);

            return $logDocument;
        };

        // Merge in specific Web settings
        $container['view'] = function ($c) {
            $view = new \Slim\Views\Twig(dirname(__FILE__).'/View/V10/OAuth/Templates', [
                'debug' => $debug,
                'cache' => Config::get('appRoot').'/storage/.cache',
            ]);
            $twigDebug = new TwigDebugExtension();
            $view->addExtension($twigDebug);

            return $view;
        };

        // Auth - token
        $container['accessToken'] = function ($container) {
            // CORS
            if ($container['request']->isOptions()) {
                return null;
            }
            // Public routes
            if ($container['request']->getUri()->getPath() === '/about') {
                return null;
            }
            if (strpos($container['request']->getUri()->getPath(), '/oauth') === 0) {
                return null;
            }

            $basicAuthService = new BasicAuthService($container);
            $oAuthService = new OAuthService($container);

            $token = null;

            try {
                $token = $oAuthService->extractToken($container['request']);
            } catch (AuthFailureException $e) {
                // Ignore
            }

            try {
                $token = $basicAuthService->extractToken($container['request']);
                //$container['requestLog']->addRelation('basicToken', $token)->save();
            } catch (AuthFailureException $e) {
                // Ignore
            }

            if (null === $token) {
                throw new HttpException('Credentials invalid!', Controller::STATUS_UNAUTHORIZED);
            }

            return $token;
        };

        // Create Auth service (empty session at that stage)
        $container['auth'] = function ($container) {
            $authService = new AuthService($container);
            $token = $container['accessToken'];
            if (null !== $token) {
                $token = $token->toArray();
                $authService->register($token->userId, $token->permissions);
            }
            return $authService;
        };

        // Version
        $container['version'] = function ($container) {
            if ($container['request']->isOptions() || $container['request']->getUri()->getPath() === '/about' || $container['request']->getUri()->getPath() === '/oauth') {
                $versionString = Config::get(['xAPI', 'latest_version']);
            } else {
                $versionString = $container['request']->getHeaderLine('X-Experience-API-Version');
            }

            if (!$versionString) {
                throw new HttpException('X-Experience-API-Version header missing.', Controller::STATUS_BAD_REQUEST);
            }

            try {
                $version = Versioning::fromString($versionString);
            } catch (\InvalidArgumentException $e) {
                throw new HttpException('X-Experience-API-Version header invalid.', Controller::STATUS_BAD_REQUEST);
            }

            if (!in_array($versionString, Config::get(['xAPI', 'supported_versions']))) {
                throw new HttpException('X-Experience-API-Version is not supported.', Controller::STATUS_BAD_REQUEST);
            }

            return $version;
        };

        return $container;
    }

    /**
     * Initialize php-cli configuration and load services
     * @return \Psr\Container\ContainerInterface service container
     */
    public function initCliContainer($container = null)
    {
        $container = $this->initGenericContainer($container);

        $logger = new Logger('cli');

        $formatter = new \Monolog\Formatter\LineFormatter();

        $handler = new \Monolog\Handler\StreamHandler('php://stdout');
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        $container['logger'] = $logger;

        return $container;
    }

    /**
     * Boot web application (Slim App), including all routes
     * @return \Psr\Http\Message\ResponseInterface
     * @throws AppInitException
     */
    public function bootWebApp()
    {
        if (!self::$containerInstantiated) {
            throw new AppInitException('Bootrstrap; You must initiate the Bootstrapper using the static factory!');
        }

        $container = self::$containerInstance;
        $app = new SlimApp($container);

        // Slim parser override and CORS compatibility layer (Internet Explorer)
        $app->add(function ($request, $response, $next) use ($container) {

            $request->registerMediaTypeParser('application/json', function ($input) {
                return json_decode($input);
            });

            if ($request->isPost() && $request->getQueryParam('method')) {
                $method = $request->getQueryParam('method');
                $request = $request->withMethod($method);
                mb_parse_str($request->getBody(), $postData);
                $parameters = new Collection($postData);
                if ($parameters->has('content')) {
                    $string = $parameters->get('content');
                } else {
                    // Content is the only valid body parameter...everything else are either headers or query parameters
                    $string = '';
                }

                // Remove body, add headers
                $parameters->remove('content');
                $allowedHeaders = ['content-type', 'authorization', 'x-experience-api-version', 'content-length', 'if-match', 'if-none-match'];
                foreach ($parameters as $key => $value) {
                    if (in_array(strtolower($key), $allowedHeaders)) {
                        $request = $request->withHeader($key, explode(',', $value));
                        $parameters->remove($key);
                    }
                }

                // Write the string into the body
                $stream = fopen('php://memory', 'r+');
                fwrite($stream, $string);
                rewind($stream);
                $body = new \Slim\Http\Stream($stream);
                $request = $request->withBody($body)->reparseBody();

                // Query string
                $uri = $request->getUri();
                $uri = $uri->withQuery(http_build_query($parameters->all()));
                $request = $request->withUri($uri);

                // Reparse the request - override request (sort of a hack)
                $container->offsetUnset('request');
                $container->offsetSet('request', $request);
                //$container['parser']->parseRequest($request);
            }

            $response = $next($request, $response);

            return $response;
        });

        ////
        // ROUTER
        ////

        $router = new Routes();

        ////
        // Extensions
        ////

        // Load extensions (event listeners and routes) that may exist
        $extensions = Config::get('extensions');

        if ($extensions) {
            foreach ($extensions as $extension) {
                if ($extension['enabled'] === true) {
                    // Instantiate the extension class
                    $className = $extension['class_name'];
                    $extension = new $className($container);

                    // Load any xAPI event handlers added by the extension
                    $listeners = $extension->getEventListeners();
                    foreach ($listeners as $listener) {
                        $container['eventDispatcher']->addListener($listener['event'], [$extension, $listener['callable']], (isset($listener['priority']) ? $listener['priority'] : 0));
                    }

                    // Load any routes added by extension
                    $extensionRoutes = $extension->getRoutes();
                    $router->merge($extensionRoutes);
                }
            }
        }

        ////
        // SlimApp
        ////

        // fetch routes after extensions have merged theirs
        $routes = $router->all();

        foreach ($routes as $pattern => $route) {
            // register single route with methods and controller
            $app->map($route['methods'], $pattern, function ($request, $response, $args) use ($container, $route) {
                $resource = Controller::load($container, $request, $response, $route['controller']);
                // We could also throw an Exception on load and catch it here...but that might have a performance penalty? It is definitely a cleaner, more proper option.
                if ($resource instanceof \Psr\Http\Message\ResponseInterface) {
                    return $resource;
                } else {
                    $method = strtolower($request->getMethod());
                    // HEAD method needs to respond exactly the same as GET method (minus the body)
                    // Body will be removed automatically by Slim
                    if ($method === 'head') {
                        $method = 'get';
                    }
                    return $resource->$method();
                }
            });
        }

        return $app;
    }

    /**
     * Boot php-cli application (Symfony Console), including all commands
     * @return \Symfony\Component\Console\Application instance
     */
    public function bootCliApp()
    {
        $app = new CliApp(self::$containerInstance);
        return $app;
    }

    /**
     * Empty placeholder for unit testing
     * @return void
     */
    public function bootTest()
    {
        // Expose container instance so Tests can inject it into services (or use it)
        return self::$containerInstance;
    }
}
