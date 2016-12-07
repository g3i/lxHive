<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2016 Brightcookie Pty Ltd
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

namespace API\Util;

use InvalidArgumentException;

class Versioning
{
    /**
     * Prefix for class.
     **/
    const CLASSPREFIX = 'V';

    /**
     * Major version.
     *
     * @var int
     **/
    private $major = 0;

    /**
     * Minor version.
     *
     * @var int
     **/
    private $minor = 0;

    /**
     * Patch version.
     *
     * @var int
     **/
    private $patch = 0;

    /**
     * Original version String.
     *
     * @var string
     */
    private $originalVersionString;

    /**
     * Parse a string into Versionable properties.
     *
     * @throws InvalidArgumentException
     *
     * @param string $string
     *
     * @return Version
     **/
    public static function fromString($string)
    {
        // Sanity check
        if (substr_count($string, '.') !== 2) {
            throw new InvalidArgumentException(
                'Version "'.$string.'" can not be parsed into a valid SemVer major.minor.patch version'
            );
        }

        $parts = explode('.', $string);

        $versionable = new self();

        // Extra check
        if (!is_numeric($parts[0])
            || ((int) $parts[0] < 0)
            || !is_numeric($parts[1])
            || ((int) $parts[1] < 0)
            || !is_numeric($parts[2])
            || ((int) $parts[2] < 0)
        ) {
            throw new InvalidArgumentException(
                'Version "'.$string.'" can not be parsed into a valid SemVer major.minor.patch version'
            );
        }

        // Versionable parts
        $versionable
            ->setMajor((int) $parts[0])
            ->setMinor((int) $parts[1])
            ->setPatch((int) $parts[2])
            ->setOriginalVersionString($string);

        return $versionable;
    }

    public function generateClassNamespace()
    {
        return self::CLASSPREFIX.$this->getMajor().$this->getMinor();
    }

    /**
     * Gets the value of major.
     *
     * @return mixed
     */
    public function getMajor()
    {
        return $this->major;
    }

    /**
     * Sets the value of major.
     *
     * @param mixed $major the major
     *
     * @return self
     */
    public function setMajor($major)
    {
        $this->major = $major;

        return $this;
    }

    /**
     * Gets the value of minor.
     *
     * @return mixed
     */
    public function getMinor()
    {
        return $this->minor;
    }

    /**
     * Sets the value of minor.
     *
     * @param mixed $minor the minor
     *
     * @return self
     */
    public function setMinor($minor)
    {
        $this->minor = $minor;

        return $this;
    }

    /**
     * Gets the value of patch.
     *
     * @return mixed
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * Sets the value of patch.
     *
     * @param mixed $patch the patch
     *
     * @return self
     */
    public function setPatch($patch)
    {
        $this->patch = $patch;

        return $this;
    }

    /**
     * Gets the Original version String.
     *
     * @return originalVersionString
     */
    public function getOriginalVersionString()
    {
        return $this->originalVersionString;
    }

    /**
     * Sets the Original version String.
     *
     * @param string $originalVersionString the original version string
     *
     * @return self
     */
    public function setOriginalVersionString($originalVersionString)
    {
        $this->originalVersionString = $originalVersionString;

        return $this;
    }
}
