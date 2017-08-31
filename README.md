
# ![lxHive](./public/assets/images/lxHive.logo.png)

* v0.9.1
* supports xAPI specs <= 1.0.2

[![Circle CI](https://circleci.com/gh/Brightcookie/lxHive/tree/0.9.1.svg?style=svg)](https://circleci.com/gh/Brightcookie/lxHive/tree/0.9.1)

## <a name="introduction" />Introduction

**lxHive** is a fast and lightweight open source xAPI conformant Learning Record Store (LRS).
**lxHive** logs and returns activity statements as defined in the [Experience API specification](https://github.com/adlnet/xAPI-Spec) (formerly TinCan API) currently at xAPI Version 1.0.2.

The Experience API (also referred to as 'xAPI') is a learning software specification that allows online learning content and systems to [interact](https://tincanapi.com/overview/) allowing recording and tracking of all types of learning experiences. It is designed to replace the legacy SCORM Standard and is steered by the US Dept. of Defense [ADL](http://www.adlnet.gov/) (Advanced Distributed Learning). It allows for the efficient aggregation and analysis of learning data as well as allowing learning designers a flexible and intelligent way to design better learning experiences. The Experience API is able to accept learning experiences from any device and/or medium (mobile, tablet, desktop), both in an offline as well as online mode.

The results of learning experiences are stored in a Learning Record Store (LRS). The LRS is defined as part of the Experience API Specification and controls at its core the following functions:

1. authentication of authorised users
2. validation of compliance to the xAPI Standard
3. the storage of learning related data
4. retrieval of learning related data

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

* PHP >= 5.4, with [mongo extension](http://php.net/manual/en/mongo.installation.php) installed
* (optional) PHPUnit to run tests.
* .htaccess enabled (or similar HTTP rewrite function)
* [Composer](https://getcomposer.org/) installed
* [Mongo DB](https://www.mongodb.org/) installed (supports Mongo 3.x)
* [OpenSSL](https://www.openssl.org/)

#### Notes:

* Make sure you have set the `date.timezone` setting in your php.ini

### Setup

* see also our Wiki for a comprehensive [step-bystep guide](https://github.com/Brightcookie/lxHive/wiki/Step-by-step:-Install-lxHive-and-setup-authentication-for-your-app)

#### 1. Application install and set-up

1. Install dependencies via `composer install`.
2. Point your server's `DocumentRoot` directive to the `public` folder
3. Set up your database & client account:

```bash

# Browse to application root
$ cd /<path_to_application_root>
# View available commands
$ ./X
# Set up database
$ ./X setup:db
# Set up OAuth scopes
$ ./X setup:oauth
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

## Contributors

The Brightcookie team

* Jakob Murko - systems architect, lead developer
* Leo Gaggl - creator, mentor, specs
* Kien Vu - legacy support, application development
* Matthew Smith - initial alpha prototype development & spec
* Joerg Boeselt - tests, specs, pm
