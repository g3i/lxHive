# ![lxHive](./public/assets/images/lxHive.logo.png)

* Current release: **0.10.1**
* Supports xAPI spec <= 1.0.3

[![CircleCI branch](https://img.shields.io/circleci/project/github/g3i/lxHive/master.svg)](https://circleci.com/gh/g3i/lxHive/tree/master)
[![lx-Test-Suite](https://img.shields.io/badge/lx--Test--Suite-82.75%25-yellowgreen.svg)](https://github.com/g3i/lx-Test-Suite)
[![lrs-conformance-test-suite](https://img.shields.io/badge/lrs--conformance--test--suite-86.02%25-yellowgreen.svg)](https://github.com/adlnet/lrs-conformance-test-suite)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/9e0e6f28-b099-4c84-ad85-ccf4de70d6a6.svg)](https://insight.sensiolabs.com/projects/9e0e6f28-b099-4c84-ad85-ccf4de70d6a6)
[![GitHub issues](https://img.shields.io/github/issues/g3i/lxHive.svg)](https://github.com/g3i/lxHive/issues)
[![GitHub forks](https://img.shields.io/github/forks/g3i/lxHive.svg)](https://github.com/g3i/lxHive/network)
[![GitHub stars](https://img.shields.io/github/stars/g3i/lxHive.svg)](https://github.com/g3i/lxHive/stargazers)
[![GitHub license](https://img.shields.io/badge/license-AGPL-blue.svg)](https://raw.githubusercontent.com/g3i/lxHive/master/LICENSE.md)

## 1. <a name="introduction" />Introduction

**lxHive** is a fast and lightweight open source xAPI conformant Learning Record Store (LRS).
**lxHive** logs and returns activity statements as defined in the [Experience API specification](https://github.com/adlnet/xAPI-Spec) (formerly TinCan API) currently at xAPI Version 1.0.3.

The Experience API (also referred to as 'xAPI') is a learning software specification that allows online learning content and systems to [interact](https://tincanapi.com/overview/) allowing recording and tracking of all types of learning experiences. It is designed to replace the legacy SCORM Standard and is steered by the US Dept. of Defense [ADL](http://www.adlnet.gov/) (Advanced Distributed Learning). It allows for the efficient aggregation and analysis of learning data as well as allowing learning designers a flexible and intelligent way to design better learning experiences. The Experience API is able to accept learning experiences from any device and/or medium (mobile, tablet, desktop), both in an offline as well as online mode.

The results of learning experiences are stored in a Learning Record Store (LRS). The LRS is defined as part of the Experience API Specification and controls at its core the following functions:

1. Authentication of authorised users
2. Validation of compliance to the xAPI Standard
3. The storage of learning related data
4. Retrieval of learning related data

The application uses [MongoDB](https://www.mongodb.org/) and [PHP](http://php.net/) and should be easy to install on any web server. It supports Basic Authentication, OAuth 2.0 (Authorization Code Grant) and supports pluggable file storage mechanisms.

## 2. <a name="license" />License

* GNU GPL v3

## 3. <a name="xAPi-Endpoints" />Document storage endpoints

| endpoint              | xAPI version  | PUT   | POST  | GET   | DELETE | Notes                                        | Links
| ---                   | ---           |:-----:|:-----:|:-----:|:------:| ---                                          |---
|  /about               | 1.0.2         | -     | -     | x     | -      | (JSON) info about LRS                        | [xAPI, section 7.7](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#77-about-resource)
|  /statements          | 1.0.2         | x     | x     | x     | -      | (JSON) create, retrieve xAPI statements      | [xAPI, section 7.2](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#72-statement-api)
|  /activities          | 1.0.2         | -     | -     | x     | -      | (JSON) retrieve s single activity            | [xAPI, section 7.5](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#75-activity-profile-api)
|  /activities/state    | 1.0.2         | x     | x     | x     | x      | (JSON) CRUD - state(s) of an activity        | [xAPI, section 7.4](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#74-state-api)
|  /activities/profile  | 1.0.2         | x     | x     | x     | x      | (JSON) CRUD - profile(s) of an activity      | [xAPI, section 7.5](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#75-activity-profile-api)
|  /agents              | 1.0.2         | -     | -     | x     | -      | (JSON) retrieve a single agent               | [xAPI, section 7.6](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#76-agent-profile-api)
|  /agents/profile      | 1.0.2         | x     | x     | x     | x      | (JSON) CRUD - profile(s) of an actor         | [xAPI, section 7.6](https://github.com/adlnet/xAPI-Spec/blob/1.0.2/xAPI.md#76-agent-profile-api)

* see our [wiki](https://github.com/g3i/lxHive/wiki/List-of-xAPI-and-lxHive-Endpoints) for a complete list of lxHive endpoints

## 4. <a name="installation" />Installation

### Requirements

* PHP >= 5.5.9, with [MongoDB extension](http://php.net/manual/en/class.mongodb.php) installed
* (optional) PHPUnit to run tests.
* .htaccess enabled (or similar HTTP rewrite function)
* [Composer](https://getcomposer.org/) installed
* [Mongo DB](https://www.mongodb.org/) installed (requires version >= 3.0)
* [OpenSSL](https://www.openssl.org/)

#### Notes:

* Make sure you have set the `date.timezone` setting in your php.ini
* lxHive >= 0.10.0 supports **PHP 7.x**
* since lxHive 0.10.0 we switched the PHP Mongo driver from `mongo` (deprecated) to `mongodb`

### Setup

* You'll find a comprehensive [setup guide](https://github.com/g3i/lxHive/wiki/Step-by-step:-Install-lxHive-and-setup-authentication-for-your-app) here

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

## 3. Development

### Documentation

* [Contributing guidelines](CONTRIBUTING.md)
* [lxHive Wiki](https://github.com/g3i/lxHive/wiki)
* Compile code documentation: run `sh generate-docs.sh` from project root (file must be executable)

### Unit testing

* [Instructions](tests/readme.md)

### Benchmarking

* [Instructions](benchmarks/readme.md)

## 4. Contributors

The [G3 International](https://g3i.com.au/) team

* Jakob Murko - systems architect, lead developer
* Leo Gaggl - creator, mentor, conformance
* Joerg Boeselt - lead developer, project and community manager, tests, conformance
* Matthew Smith - alpha prototype
