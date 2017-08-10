
# ![lxHive](./public/assets/images/lxHive.logo.png)

* Current version: **v0.9.5**
* Supports xAPI spec <= 1.0.3

[![CircleCI branch](https://img.shields.io/circleci/project/github/Brightcookie/lxHive/development.svg)](https://circleci.com/gh/Brightcookie/lxHive/tree/development)
[![lx-Test-Suite](https://img.shields.io/badge/lx--Test--Suite-66.63%25-yellow.svg)](https://github.com/Brightcookie/lx-Test-Suite)
[![lrs-conformance-test-suite](https://img.shields.io/badge/lrs--conformance--test--suite-74.20%25-yellowgreen.svg)](https://github.com/adlnet/lrs-conformance-test-suite)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/9e0e6f28-b099-4c84-ad85-ccf4de70d6a6.svg)](https://insight.sensiolabs.com/projects/9e0e6f28-b099-4c84-ad85-ccf4de70d6a6)
[![GitHub issues](https://img.shields.io/github/issues/Brightcookie/lxHive.svg)](https://github.com/Brightcookie/lxHive/issues)
[![GitHub forks](https://img.shields.io/github/forks/Brightcookie/lxHive.svg)](https://github.com/Brightcookie/lxHive/network)
[![GitHub stars](https://img.shields.io/github/stars/Brightcookie/lxHive.svg)](https://github.com/Brightcookie/lxHive/stargazers)
[![GitHub license](https://img.shields.io/badge/license-AGPL-blue.svg)](https://raw.githubusercontent.com/Brightcookie/lxHive/master/LICENSE.md)

## <a name="introduction" />Introduction

**lxHive** is a fast and lightweight open source xAPI conformant Learning Record Store (LRS).
**lxHive** logs and returns activity statements as defined in the [Experience API specification](https://github.com/adlnet/xAPI-Spec) (formerly TinCan API) currently at xAPI Version 1.0.2.

The Experience API (also referred to as 'xAPI') is a learning software specification that allows online learning content and systems to [interact](https://tincanapi.com/overview/) allowing recording and tracking of all types of learning experiences. It is designed to replace the legacy SCORM Standard and is steered by the US Dept. of Defense [ADL](http://www.adlnet.gov/) (Advanced Distributed Learning). It allows for the efficient aggregation and analysis of learning data as well as allowing learning designers a flexible and intelligent way to design better learning experiences. The Experience API is able to accept learning experiences from any device and/or medium (mobile, tablet, desktop), both in an offline as well as online mode.

The results of learning experiences are stored in a Learning Record Store (LRS). The LRS is defined as part of the Experience API Specification and controls at its core the following functions:

1. Authentication of authorised users
2. Validation of compliance to the xAPI Standard
3. The storage of learning related data
4. Retrieval of learning related data

The application uses [MongoDB](https://www.mongodb.org/) and [PHP](http://php.net/) and should be easy to install on any web server. It supports Basic Authentication, OAuth 2.0 (Authorization Code Grant) and supports pluggable file storage mechanisms.

## <a name="license" />License

* GNU GPL v3

## <a name="xAPi-Endpoints" />Document storage endpoints

| endpoint              | xAPI version  | PUT   | POST  | GET   | DELETE | Notes                                        | Links
| ---                   | ---           |:-----:|:-----:|:-----:|:------:| ---                                          |---
|  /about               | 1.0.2         | -     | -     | x     | -      | (JSON) info about LRS                        | [xAPI, section 7.7](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#77-about-resource)
|  /statements          | 1.0.2         | x     | x     | x     | -      | (JSON) create, retrieve xAPI statements      | [xAPI, section 7.2](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#72-statement-api)
|  /activities          | 1.0.2         | -     | -     | x     | -      | (JSON) retrieve s single activity            | [xAPI, section 7.5](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#75-activity-profile-api)
|  /activities/state    | 1.0.2         | x     | x     | x     | x      | (JSON) CRUD - state(s) of an activity        | [xAPI, section 7.4](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#74-state-api)
|  /activities/profile  | 1.0.2         | x     | x     | x     | x      | (JSON) CRUD - profile(s) of an activity      | [xAPI, section 7.5](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#75-activity-profile-api)
|  /agents              | 1.0.2         | -     | -     | x     | -      | (JSON) retrieve a single agent               | [xAPI, section 7.6](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#76-agent-profile-api)
|  /agents/profile      | 1.0.2         | x     | x     | x     | x      | (JSON) CRUD - profile(s) of an actor         | [xAPI, section 7.6](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#76-agent-profile-api)

* see our [wiki](https://github.com/Brightcookie/lxHive/wiki/List-of-xAPI-and-lxHive-Endpoints) for a complete list of lxHive endpoints

## <a name="installation" />Installation

### Requirements

* PHP >= 5.5.9, with [MongoDB extension](http://php.net/manual/en/class.mongodb.php) installed
* (optional) PHPUnit to run tests.
* .htaccess enabled (or similar HTTP rewrite function)
* [Composer](https://getcomposer.org/) installed
* [Mongo DB](https://www.mongodb.org/) installed (requires version >= 3.0)
* [OpenSSL](https://www.openssl.org/)

#### Notes:

* Make sure you have set the `date.timezone` setting in your php.ini
* lxHive >= 0.9.5 supports **PHP 7.x**
* since lxHive 0.9.5 we switched the PHP Mongo driver from `mongo` (deprecated) to `mongodb`

### Setup

* *Note: Check out our Wiki for a more comprehensive [step-by-step guide].(https://github.com/Brightcookie/lxHive/wiki/Step-by-step:-Install-lxHive-and-setup-authentication-for-your-app)*

#### 1. Application install and set-up

1. Install dependencies via `composer install --no-dev -o`.
2. Point your server's `DocumentRoot` directive to the `public` folder
3. Set up your database & client account:

```bash

# Browse to application root
$ cd /<path_to_application_root>
# View available commands
$ ./X
# Run the setup
$ ./X setup
# Create a new user
$ ./X user:create

```

#### Notes:

* As an administrator you should first create a new user and authentication token for yourself and assign the *super* role to it.

#### 2. Create autentication records for your app

```
./X auth:basic:create     # Create a new basic auth token
./X oauth:client:create   # Or create a new OAuth client (human login)
```

#### 3. Set-up file Storage and extended config

1. Optionally: Further customise your configuration in `src/xAPI/Config/Config.yml`

    * TODO: explain options

2. Set up you local file system:

    * Create your storage directory as defined in `Config.yml > filesystem.local`.
    * Create the required `files` and `log` sub directories.
    * Assign the appropiate `r\w` permissions for your system.

Default file storage structure:

```
[lxHivE]
    |_ [storage]
    |   |_ [files]
    |   |_ [logs]
    |
    ...
```

## Documentation

See the Wiki and the `docs` directory for more detailed documentation.

Compile code documentation:

run `sh generate-docs.sh` from project root (file must be executable)

## Contributors

The Brightcookie team

* Jakob Murko - systems architect, lead developer
* Leo Gaggl - creator, mentor, specs
* Kien Vu - legacy support, application development
* Matthew Smith - initial alpha prototype development & spec
* Joerg Boeselt - tests, development, specs, pm
