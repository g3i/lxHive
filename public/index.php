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

// Require the autoloader
require __DIR__.'/../vendor/autoload.php';

use Slim\Slim;
use BurningDiode\Slim\Config as Config;
use Flynsarmy\SlimMonolog\Log as Logger;
use Monolog\Handler\StreamHandler;
use API\Resource;
use League\Url\Url;
use Slim\Helper\Set;
use Sokil\Mongo\Client;
use API\Service\Auth\OAuth as OAuthService;
use API\Service\Auth\Basic as BasicAuthService;
use API\Service\Log as LogService;
use Slim\Views\Twig;
use API\Service\Auth\Exception as AuthFailureException;
use API\Util\Versioning;

// Set up a new Slim instance - default mode is production (it is overriden with SLIM_MODE environment variable)
$app = new Slim();

$appRoot = dirname(__DIR__);

// Prepare config loader
Config\Yaml::getInstance()->addParameters(['app.root' => $appRoot]);
// Default config
try {
    Config\Yaml::getInstance()->addFile($appRoot.'/src/xAPI/Config/Config.yml');
} catch (\Exception $e) {
    if (PHP_SAPI === 'cli' && ((isset($argv[1]) && $argv[1] === 'setup:db') || (isset($argv[0]) && !isset($argv[1])))) {
        // Database setup in progress, ignore exception
    } else {
        throw new \Exception('You must run the setup:db command using the X CLI tool!');
    }
}

// Use Mongo's native long int
ini_set('mongo.native_long', 1);

// Only invoked if mode is "production"
$app->configureMode('production', function () use ($app, $appRoot) {
    // Add config
    Config\Yaml::getInstance()->addFile($appRoot.'/src/xAPI/Config/Config.production.yml');

    // Set up logging
    $logger = new Logger\MonologWriter([
        'handlers' => [
            new StreamHandler($appRoot.'/storage/logs/production.'.date('Y-m-d').'.log'),
        ],
    ]);

    $app->config('log.writer', $logger);
});

// Only invoked if mode is "development"
$app->configureMode('development', function () use ($app, $appRoot) {
    // Add config
    Config\Yaml::getInstance()->addFile($appRoot.'/src/xAPI/Config/Config.development.yml');

    // Set up logging
    $logger = new Logger\MonologWriter([
        'handlers' => [
            new StreamHandler($appRoot.'/storage/logs/development.'.date('Y-m-d').'.log'),
        ],
    ]);

    $app->config('log.writer', $logger);
});

if (PHP_SAPI !== 'cli') {
    $app->url = Url::createFromServer($_SERVER);
}

// Error handling
$app->error(function (\Exception $e) {
    $code = $e->getCode();
    if ($code < 100) {
        $code = 500;
    }
    Resource::error($code, $e->getMessage());
});

// Database layer setup
$app->hook('slim.before', function () use ($app) {
    $app->container->singleton('mongo', function () use ($app) {
        $client = new Client($app->config('database')['host_uri']);
        $client->map([
            $app->config('database')['db_name']  => '\API\Collection',
        ]);
        $client->useDatabase($app->config('database')['db_name']);

        return $client;
    });
});

// CORS compatibility layer (Internet Explorer)
$app->hook('slim.before.router', function () use ($app) {
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
$app->hook('slim.before.dispatch', function () use ($app) {
    // Version
    $app->container->singleton('version', function () use ($app) {
        if ($app->request->isOptions() || $app->request->getPathInfo() === '/about' || strpos(strtolower($app->request->getPathInfo()), '/oauth') === 0) {
            $versionString = $app->config('xAPI')['latest_version'];
        } else {
            $versionString = $app->request->headers('X-Experience-API-Version');
        }

        if ($versionString === null) {
            throw new \Exception('X-Experience-API-Version header missing.', Resource::STATUS_BAD_REQUEST);
        } else {
            try {
                $version = Versioning::fromString($versionString);
                return $version;
            } catch (\InvalidArgumentException $e) {
                throw new \Exception('X-Experience-API-Version header invalid.', Resource::STATUS_BAD_REQUEST);
            }
        }
    });

    // Request logging 
    $app->container->singleton('requestLog', function () use ($app) {
        $logService = new LogService($app);
        $logDocument = $logService->logRequest($app->request);

        return $logDocument;
    });

    // Auth - token
    $app->container->singleton('auth', function () use ($app) {
        if (!$app->request->isOptions() && !($app->request->getPathInfo() === '/about')) {
            $basicAuthService = new BasicAuthService($app);
            $oAuthService = new OAuthService($app);

            $token = null;

            try {
                $token = $oAuthService->extractToken($app->request);
                $app->requestLog->addRelation('oAuthToken', $token)->save();
            } catch (AuthFailureException $e) {
                // Ignore
            }

            try {
                $token = $basicAuthService->extractToken($app->request);
                $app->requestLog->addRelation('basicAuthToken', $token)->save();
            } catch (AuthFailureException $e) {
                // Ignore
            }

            if (null === $token) {
                throw new \Exception('Credentials invalid!', Resource::STATUS_UNAUTHORIZED);
            }

            return $token;
        }
    });

    // Load Twig only if this is a request where we actually need it!
    if (strpos(strtolower($app->request->getPathInfo()), '/oauth') === 0) {
        $twigContainer = new Twig();
        $app->container->singleton('view', function () use ($twigContainer) {
            return $twigContainer;
        });
    }

    // Content type check 
    if (($app->request->isPost() || $app->request->isPut()) && $app->request->getPathInfo() === '/statements' && !in_array($app->request->getMediaType(), ['application/json', 'multipart/mixed', 'application/x-www-form-urlencoded'])) {
        // Bad Content-Type
        throw new \Exception('Bad Content-Type.', Resource::STATUS_BAD_REQUEST);;
    }
});

// Start with routing - dynamic for now
// Get
$app->get('/:resource(/(:action)(/))', function ($resource, $subResource = null) use ($app) {
    $resource = Resource::load($app->version, $resource, $subResource);
    if ($resource === null) {
        Resource::error(Resource::STATUS_NOT_FOUND, 'Cannot find requested resource.');
    } else {
        $resource->get();
    }
});
// Post
$app->post('/:resource(/(:action)(/))', function ($resource, $subResource = null) use ($app) {
    $resource = Resource::load($app->version, $resource, $subResource);
    if ($resource === null) {
        Resource::error(Resource::STATUS_NOT_FOUND, 'Cannot find requested resource.');
    } else {
        $resource->post();
    }
});
// Put
$app->put('/:resource(/(:action)(/))', function ($resource, $subResource = null) use ($app) {
    $resource = Resource::load($app->version, $resource, $subResource);
    if ($resource === null) {
        Resource::error(Resource::STATUS_NOT_FOUND, 'Cannot find requested resource.');
    } else {
        $resource->put();
    }
});
// Delete
$app->delete('/:resource(/(:action)(/))', function ($resource, $subResource = null) use ($app) {
    $resource = Resource::load($app->version, $resource, $subResource);
    if ($resource === null) {
        Resource::error(Resource::STATUS_NOT_FOUND, 'Cannot find requested resource.');
    } else {
        $resource->delete();
    }
});
// Options
$app->options('/:resource(/(:action)(/))', function ($resource, $subResource = null) use ($app) {
    $resource = Resource::load($app->version, $resource, $subResource);
    if ($resource === null) {
        Resource::error(Resource::STATUS_NOT_FOUND, 'Cannot find requested resource.');
    } else {
        $resource->options();
    }
});
// Not found
$app->notFound(function () {
    Resource::error(Resource::STATUS_NOT_FOUND, 'Cannot find requested resource.');
});
$app->run();
