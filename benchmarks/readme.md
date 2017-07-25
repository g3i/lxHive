# Benchmarks

lxHive supports testing with [PhpBench](http://phpbench.readthedocs.org/).
PhpBench is included as a `require-dev` dependency.

A `phpbench.json.dist` file is setup in the project root folder of your lxHive app.

You can safely create a custom configuration file (`phpbench.json`) in the root folder. This file is excluded  via .gitignore


## Structure

| namespace             | folder                     | notes |
|---                    |---                         |---    |
| `\Bench`              | `benchmarks/*`             |       |

* Benchmark Classes have to have the `*Bench` suffix, i.e. `BootstrapBench`
* Benchmark methods have to have the `bench*` prefix, i.e. `benchFactory()`

Benchmarks are under `.gitignore` (except example) so you can freely create your tests.

See documentation: http://phpbench.readthedocs.io/en/latest/writing-benchmarks.html

## Usage

```bash
./vendor/bin/phpbench run <options>
```
