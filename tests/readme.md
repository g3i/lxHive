# Unit tests

lxHive supports testing with [PHPUnit](https://phpunit.de/).
PHPunit is included as a `require-dev` dependency.
A `phpunit.xml` file is setup in the project root folder of your lxHive app.

## Structure

The `tests` directory contains two sub-directories directories:

| namespace     | folder                     | notes |
|---            |---                         |---    |
| `\Tests\Unit` | `./Unit/*`                 | Assorted Unit tests, tests for issues, feature tests etc |
| `\Tests\API`  | `./src/xAPI/*`             | Mirrors the app code's `\API` namespace and folder structure |

Test files have to follow the [*Test.php](https://phpunit.de/manual/current/en/organizing-tests.html#organizing-tests.filesystem) suffix pattern, i.e. `FoobarTest.php`

An ExampleTest.php file is provided in the `Unit` test directory.

## Installation

Some tests may require a http-like connection x to your LRS, including basic authentication and xAPI headers.

* Copy `Config.template.php` to `Config.php`
* Update `Config::$lrs` with your lrs data including valid authentication

## Usage

```bash
./vendor/bin/phpunit
```

For more information and available command line options see the [PHPUnit](https://phpunit.de/manual/current/en/textui.html) documentation
