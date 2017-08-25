# Benchmarks

lxHive supports testing with [PhpBench](http://phpbench.readthedocs.org/).

A `phpbench.json.dist` file is setup in the project root folder of your lxHive app.

## Installing PHPBench

PHPBench requires PHP >= 7.1 and is therefore NOT included as a composer dependency.

You can either [install PHPBench globally](http://phpbench.readthedocs.io/en/latest/installing.html) or fetch the Phar locally into lxHive root.

```
cd <lxhive-root>$
curl -o phpbench.phar https://phpbench.github.io/phpbench/phpbench.phar
curl -o phpbench.phar.pubkey https://phpbench.github.io/phpbench/phpbench.phar.pubkey

```

`phpbench.phar` and `phpbench.pharpubkey` are excluded via .gitignore.

## Usage

```bash
php phpbench.phar run <options>
```

You can create your custom configuration file (`phpbench.json`) in the root folder. This file is excluded via .gitignore

## Structure

| namespace             | folder                     | notes |
|---                    |---                         |---    |
| `\Bench`              | `benchmarks/*`             |       |

* Benchmark Classes have to have the `*Bench` suffix, i.e. `BootstrapBench`
* Benchmark methods have to have the `bench*` prefix, i.e. `benchFactory()`

Benchmarks are under `.gitignore` (except example) so you can freely create your tests.

See documentation: http://phpbench.readthedocs.io/en/latest/writing-benchmarks.html
