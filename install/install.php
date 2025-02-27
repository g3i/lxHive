#!/usr/bin/env php

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
 //'mongodb://'. DB_USERNAME .':' . DB_PASSWORD . '@'. DB_HOST . ':27017/'

// Require the autoloader

$app_path = dirname(dirname(__FILE__));
$src_path = dirname(__FILE__);
$cfg_path = $app_path.'/src/xAPI/Config';

$autoloader = $app_path.'/vendor/autoload.php';
if (!is_file($autoloader)) {
    print("Please install lxHvie dependencies first by running the following command, then re-run this script:\n\n");
    print("\t composer install --working-dir=".$app_path."\n\n");
    exit(1);
}
require $autoloader;

use Symfony\Component\Yaml\Yaml;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;

use API\Bootstrap;
use API\Config;

use API\Admin\Setup;
use API\Admin\Auth;
use API\Admin\Validator;
use API\Admin\LrsReport;
use API\Admin\User as UserAdmin;
use API\Admin\Auth as AuthAdmin;

use API\Util\Collection;

use API\Admin\AdminException;
use API\AppInitException;

function merge_env($contents) {
    $res = preg_replace_callback('/(?:\${([^\}]+)\})/', function($matches) {
        $env = getenv($matches[1]);
        if (!$env) {
            print(" - [WARN] enviromnent var '".$matches[1]."' not set or empty\n");
        }
        return $env;
    }, $contents);
    return $res;
}

function load_yaml($file) {
    $contents = file_get_contents($file);
    if (false === $contents) {
        throw new AdminException('Error reading file `'.$file.'`. Make sure the file exists and is readable.');
    }

    $replaced = merge_env($contents);
    $data = null;

    try {
        $data = Yaml::parse($replaced, true);
    } catch (\Exception $e) {
        // @see \Symfony\Component\Yaml\Yaml::parse()
        throw new AdminException('Error parsing data from file `'.$file.'`');
    }

    if (!$data) {
        throw new AdminException('Error parsing data from file `'.$file.'`: Empty data.');
    }

    return new Collection($data);
}

function lrs_report() {
    global $app_path;
    $res = shell_exec('cd '.$app_path.' && ./X status');
    echo($res);
}

function _log($message, $prefix = '', $out=true) {
    $suffix = "\n";
    $str = $prefix.$message.$suffix;
    if ($out) {
        print($str);
    }
    return $str;
}

function _tick($message)  {
    _log($message, "\e[32m"." [OK] "."\e[0m");
}

function _skip($message)  {
    _log($message, "\e[33m"." [SKIP] "."\e[0m");
}

function _tab($message)  {
    _log("\t"."\e[37m".$message."\e[0m");
}

function _copy_this($message)  {
    $empty = str_repeat(' ', strlen($message)) ;
    _tab("\e[0;90;47m ".$empty." \e[0m");
    _tab("\e[0;90;47m ".$message." \e[0m");
    _tab("\e[0;90;47m ".$empty." \e[0m");
}

// - prepare

$check = $src_path.'/LRS.yml';
if (!is_file($check)) {
    throw new AdminException('Missing configuration file: `'.$check.'`');
}

$def_config = $cfg_path.'/Config.yml';
if (file_exists($def_config)) {
    if (in_array('force', $argv) || in_array('-f', $argv)) {
        unlink($def_config);
        _tick(" - removed:".$def_config."\n");
    } else {
        _log("A config.yml already exists: ".$def_config);
        _log("You can force a complete reinstall by using the 'force' option:\n");
        _tab($argv[0]." force");
        _log("\nNote: This option will also delete existing database(s).");
        exit(0);
    }
}

// - boot app without config

try {
    $bootstrapper = Bootstrap::factory(Bootstrap::Config);
} catch (AppInitException $e) {
    // nothing
}

$setup = new Setup();
$validator = new Validator();
$container = null;
$install = null;

// - install default config(s)

$default_configs = $setup->installDefaultConfig();

_tick('created default configurations');
_tab($cfg_path.': '.json_encode($default_configs));

// - merge LRS config

$default = load_yaml($cfg_path.'/Config.yml');
$app_version = $default->get('version', 'app:missing');

$install = load_yaml($src_path.'/LRS.yml');
$install_version = $install->get('version', 'install:missing');

if ($install_version != $app_version) {
    throw new AdminException('Install LRS.yml version does not match lxHive app version: \''.$install_version.'\' != \''.$app_version.'\'');
}

$merge_config = $install->get(['config'], []);
foreach ($merge_config as $mode => $vals) {
    $name = ($mode == 'default') ? 'Config.yml' : 'Config.'.$mode.'.yml';
    $setup->updateYaml($name, $vals);
    _tick('updated '.$name);
}

// - reboot app with new config

Bootstrap::reset();
$boot = Bootstrap::factory(Bootstrap::Web);
$container = $boot->initGenericContainer();
$supported_auth_scopes = Config::get(['xAPI', 'supported_auth_scopes']);

_tick('updated Config.yml');
_tab('merged: '. json_encode($merge_config));
_tab('supported_auth_scopes: '.json_encode(array_keys($supported_auth_scopes), JSON_UNESCAPED_SLASHES));

// - connect to mongo and set up db

$client = new Manager(Config::get(['storage', 'Mongo', 'host_uri']));
$db_name = Config::get(['storage', 'Mongo', 'db_name']);
$validator->validateMongoName($db_name);

// delete exiting
$cmd = new Command([ 'dropDatabase' => 1 ]);
$client->executeCommand($db_name, $cmd);

// install new
$setup->verifyDbVersion($container);
$setup->installDb($container);

$cmd = new Command([ 'listCollections' => [ 'nameOnly' => 1] ]);
$cursor = $client->executeCommand($db_name, $cmd);
$collections  = [];
foreach($cursor as $item) {
    $collections[] = $item->name;
}

_tick('installed DB '.$db);
_tab('collections: '.json_encode($collections));

// - set up file storage

$setup->installFileStorage();
_tick('installed file storage');
_tab('storage dir: '.json_encode(array_values(array_diff(scandir($app_path.'/storage'), ['.', '..']))));

// - LRS users and basic auth

$userAdmin = new UserAdmin($container);
$authAdmin = new AuthAdmin($container);

//  - LRS.users

$created_users = [];
$created_tokens = [];

$user_entries = $install->get('users', []);
if (!$user_entries) {
    _skip('create LRS users');
}

foreach ($user_entries as $entry) {

    // create user
    $validator->validateEmail($entry['email']);
    $validator->validatePassword($entry['password']); ///TODO autogenerate a password instead ?
    $validator->validateXapiPermissions($entry['permissions'], $supported_auth_scopes);

    $permissions = $userAdmin->mergeInheritedPermissions($entry['permissions']);
    $cursor = $userAdmin->addUser($entry['name'], $entry['description'], $entry['email'], $entry['password'], $permissions);
    $user = $cursor->toArray(); //stdClass

    $created_users[] = [
        'id' => $user->_id,
        'name' => $user->name,
        'email' => $user->email,
    ];

    // optionally, create a basic auth token for this user
    if ($entry['_basic_auth']) {
        $name = 'auto_token '.$user->name;
        $description = 'imported from '.$src_path.'/LRS.yml';
        $expiresAt = null ; //TODO
        $key = null;
        $secret = null;
        $cursor = $authAdmin->addToken($name, $description, $expiresAt, $user, $permissions, $key, $secret);
        $token = $cursor->toArray(); //stdClass
        $created_tokens[] = [
            'userId' => $token->userId,
            'userName' => $user->name,
            'key' => $token->key,
            'secret' => $token->secret,
        ];
    }

}

if ($created_users) {
    _tick('Created '.count($created_users).' LRS Users');
    foreach($created_users as $usr) {
        _tab(json_encode($usr));
    }
}

if($created_tokens) {
    _tab("");
    _tab("\e[31m !!! \e[0m"."Please copy and store away the following auth tokens:");
    _tab("");
    foreach($created_tokens as $tok) {
        _copy_this(json_encode($tok));
        _tab("");
    }
}

// - LRS.oauth

$created_oauths = [];

$oauth_entries = $install->get('oauth', []);
if (!$oauth_entries) {
    _skip('create oAuth clients');
}

foreach ($oauth_entries as $entry) {
    $validator->validateName($entry['name']);
    $validator->validateRedirectUri($entry['redirectUri']);

    $cursor = $authAdmin->addOAuthClient($entry['name'], $entry['description'], $entry['redirectUri']);
    $client = $cursor->toArray(); //stdClass

    $created_oauths[] = [
        'name' => $client->name,
        'redirectUri' => $client->redirectUri,
        'clientId' => $client->clientId,
        'secret' => $client->secret,
    ];
}

if ($created_oauths) {
    _tick('Created '.count($created_oauths).' oAuth clients');

    _tab("");
    _tab("\e[31m !!! \e[0m"."Please copy and store away the following oAuth client settings:");
    _tab("");
    foreach($created_oauths as $tok) {
        _copy_this(json_encode($tok, JSON_UNESCAPED_SLASHES));
        _tab("");
    }
}

_log("\n------------------------");
_log("\e[32m"."       FINISHED "."\e[0m");
_log("------------------------\n");

// lrs_report();
