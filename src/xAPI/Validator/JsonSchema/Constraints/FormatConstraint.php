<?php
/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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


namespace API\Validator\JsonSchema\Constraints;

use JsonSchema;
use Ramsey\Uuid\Uuid;

/**
 * Validates against the 'format' property
 */
class FormatConstraint extends JsonSchema\Constraints\FormatConstraint
{
    /**
     * Invokes the validation of an element
     *
     * @param mixed            $value
     * @param mixed            $schema
     * @param JsonPointer|null $path
     * @param mixed            $i
     * @throws \JsonSchema\Exception\ExceptionInterface
     */
    public function check($element, $schema = null, JsonSchema\Entity\JsonPointer $path = null, $i = null)
    {

        if (!isset($schema->format)) {
            return;
        }

        switch ($schema->format) {
            // @see https://en.wikipedia.org/wiki/Uniform_Resource_Identifier
            // @see https://en.wikipedia.org/wiki/Internationalized_Resource_Identifier
            // @see https://www.w3.org/TR/uri-clarification/
            // @see http://tools.ietf.org/html/rfc3987
            case 'iri':
                if (null === filter_var($element, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE)) {
                    $parsed = parse_url($element);
                    // PHP FILTER_VALIDATE_URL covers only rfc2396, non-ansi characters will fail (i.e. https://fa.wikipedia.org/wiki/یوآرآی)
                    if (false === $parsed) {
                        parent::addError($path, 'Invalid  RFC 3987 IRI format', 'format', [ 'format' => $schema->format ]);
                    }

                    $scheme = (isset($parsed['scheme'])) ? $parsed['scheme'] : null;

                    // empty scheme requires host: //example.org/scheme-relative/URI/with/absolute/path/to/resource)
                    if (!$scheme && empty($parsed['host'])) {
                        parent::addError($path, 'Invalid  RFC 3987 IRI format', 'format', [ 'format' => $schema->format ]);
                    }

                    // urn:ISSN:1535-3613
                    if ($scheme) {
                        if($scheme === 'urn' && empty($parsed['path'])) {
                            parent::addError($path, 'Invalid  RFC 3987 IRI format', 'format', [ 'format' => $schema->format ]);
                        }
                    }
                }
            break;

            case 'uuid':
                if (!Uuid::isValid($element)) {
                    $this->addError($path, 'Invalid UUID', 'format', [ 'format' => $schema->format ]);
                }
            break;

            default:
                parent::check($element, $schema, $path, $i);
            break;
        }
    }
}
