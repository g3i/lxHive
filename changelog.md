## Version 0.10.0 FutureReady

>This version is **NOT COMPATIBLE** with older lxHive versions. (server requirements, codebase, database model).

Currently there are no plans to release database migration functionality. Please launch a request to `support@brightcookie.com.au` if you need help with migrating records from older lxHive versions.

#### Summary
Extensive changes in regards to stability, testability, reporting administration and configurability.
This release focuses on support for modern server environments and advances in an overall simplification and refactoring process.

* PHP 7 support
* Slim 3
* Replace legacy Mongo Driver with [MongoDB](http://php.net/manual/en/set.mongodb.php)
* Advanced reporting queries with the new ExtendedQuery Extension

#### Misc, General code refactor
* Consolidation of exception handling
* Code separation, restructuring and division into modules, controllers, services and APIs
* Decoupling third-party dependencies (notably Slim)
* Consistent use of dependency containers for services
* Removing logic out of index.php into new Bootstrap module; index.php now only has 3 LOC
* Support for Monolog handlers for production and development mode: ChromePHPHandler, FirePHPHandler, Streamhandler, ErrorLogHandler
* Removal of obsolete code
* Increase of code testability
* Separation of autoloading for unit tests and benchmarks
* Linting, PSR-1, CircleCI
* Improved /about endpoint includes extensions and LRS information
* Consolidation of agent and uuid processing

#### ExtendedQuery Extension (new)
* Fragmented reporting queries, closely modelled on MongoDB, query language
* Documentation in progress

#### Configuration Module (new)
* Extensive model changes
* Templates
* Globally accessible config service
* Version awareness
* New authentication scopes configuration
* Configuration model for extensions
* Documented config template

#### Bootstrap Module (new)
* New Bootstrap module loads configuration and boots app
* Boot modes: Web (default), Console, Testing, Config, None
* Service container creation according to boot modes

#### Parser Module (rewrite)
* Combining parser logic in parser module
* Switch payload parsing to stdClass

#### Controller Module (rewrite)
* Migrate controller logic from ols Resource into new "Controller" module
* Improved permission validation

#### Router Module (new)
* Lightweight and extendable router API

#### Authentication Module (rewrite)
* New centralised Auth Service is faster and easier to configure
* Removed AuthScopes collection, services and models
* Permission inheritance and user ID
* Changes to oAuth and basicAuth services

#### Database/Storage module (replaced):
* PHP 7 ready: switch to ‘ext-mongodb’, removed support for legacy `ext-mongo`
* Complete new low-level Storage API
* Performance improvements by removing all third-party dependencies
* Basic schema
* Collection Indexing
* Query expression API
* Started unifying document properties
* Statement documents include user ID

#### Admin API (new)
* Bundled administrative tasks into new API
* Input validators
* Most administrative tasks are now REST ready (next milestone)

#### Extensions API (new)
* Installation
* Configuration
* Model
* Controller
* Symfony events

#### Validator Module (changes)
* Slight improvements to JSON validator
* Debug mode for HTTP errors
* Conformance improvements

#### Admin Console (changes)
* New multistep ./X setup command
* Removed obsolete "AuthScopes" commands
* ./X status command give LRS status report
* Improved output styling and added command hints
* Strict validation for emails and passwords

#### Unit test (new)
* PHPUnit configurations, base classes, with instructions and examples
* Started migration tests
* Mongo test API

#### Benchmarking (new)
* PHPBench support and configuration

#### Third Party support
* Removed sokil/php-mongo
* Switch to Slim 3 (see above); decoupling from code
* General review of dependencies, removed obsolete ones, updated others

#### PHPDoc:
* Removed "docs" folder with compiled documents to keep documentation current
* Added bash script helper for documentation generation
* Continued to add missing code documentation
* Changes to Sami configuration

----

## Version 0.9.1

* include StatementRefs in StatementResults
* mongo log
* console improvements
* migration script
* default (token based) statement.authority
* GET /statments format param implementation
* improved oAuth views with configurable css, logo
* statement attachments (multipart): support for utf-8 and base64
* conformance improvements
