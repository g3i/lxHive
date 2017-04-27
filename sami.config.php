<?php
/**
 * Configuration for code documentation compiler
 */

define('LXHIVE_DOCS_SRC', __DIR__.'/src/xAPI');
define('LXHIVE_DOCS_BUILD', __DIR__.'/docs');

use Sami\Sami;
use Sami\Parser\Filter\TrueFilter;
use Symfony\Component\Finder\Finder;

// directories
$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(LXHIVE_DOCS_SRC);

// options
$sami = new Sami($iterator, array(
    'title' => 'lxHive',
    'build_dir' => LXHIVE_DOCS_BUILD,
    'cache_dir' => LXHIVE_DOCS_BUILD,
    'default_opened_level' => 2,
));

// Document private and protected functions/properties
$sami['filter'] = function () {
    return new TrueFilter();
};

return $sami;
