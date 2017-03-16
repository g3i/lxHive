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

// require App autoloader
require __DIR__.'/../../vendor/autoload.php';

/**
 * Separate PSR-4 autoloader for tdd tests. This keeps the App (\API) namespace and classmap files free of tests
 *
 * Code taken and adapted from https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Tests\Unit\ExampleTest
 * from <project_path>/tests/tdd/Unit/ExampleTest.php:
 *
 *      new \Tests\Unit\ExampleTest;
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */

spl_autoload_register(function ($class) {
    $prefix = 'Tests\\';
    $base_dir = __DIR__.'/' ;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
