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
 *
 * This file was adapted from sokil/php-mongo.
 * License information is available at https://github.com/sokil/php-mongo/blob/master/LICENSE
 *
 */

namespace API\Storage\Adapter\Mongo;

use FieldType;
use API\Storage\AdapterException;

class Expression implements ExpressionInterface
{
    /**
     * @var array $expression
     */
    protected $expression = [];

    /**
     * @constructor
     * @param array $array
     */
    public function __construct($array = [])
    {
        $this->expression = $array;
    }

    ////
    // self::$expression management
    ////

    /**
     * Create self::$expression from array
     * @param array $array
     *
     * @return void
     */
    public function fromArray($array)
    {
        $this->expression = $array;
    }

    /**
     * Create new instance of expression
     * @return self
     */
    public function expression()
    {
        return new self;
    }


    /**
     * Helper method for fetching self::$expresssion
     *
     * @return array
     */
    public function toArray()
    {
        return $this->expression;
    }

    /**
     * Merge expression into self::$expression
     * @param Expression $expression
     *
     * @return self
     */
    public function merge(Expression $expression)
    {
        $this->expression = array_merge_recursive($this->expression, $expression->toArray());
        return $this;
    }

    /**
     * Transform expression in different formats to canonical array form
     * @param mixed $mixed
     *
     * @return array
     * @throws AdapterException
     */
    public static function convertToArray($mixed)
    {
        // Get expression from callable
        if (is_callable($mixed)) {
            $callable = $mixed;
            $mixed = new self();
            call_user_func($callable, $mixed);
        }

        // Get expression array
        if ($mixed instanceof self) {
            $mixed = $mixed->toArray();
        } elseif (!is_array($mixed)) {
            throw new AdapterException('Mixed must be instance of \Expression');
        }

        return $mixed;
    }

    ////
    // where query
    ////

    /**
     * Performs where search.
     * @param string $field
     * @param mixed $value
     *
     * @return self
     */
    public function where($field, $value)
    {
        if (!isset($this->expression[$field]) || !is_array($value) || !is_array($this->expression[$field])) {
            $this->expression[$field] = $value;
        } else {
            $this->expression[$field] = array_merge_recursive($this->expression[$field], $value);
        }

        return $this;
    }

    /**
     * Performs whereEmpty search.
     * @param string $field
     *
     * @return self
     */
    public function whereEmpty($field)
    {
        return $this->where('$or', [
            [$field => null],
            [$field => ''],
            [$field => []],
            [$field => ['$exists' => false]]
        ]);
    }

    /**
     * Performs whereNotEmpty search.
     * @param string $field
     *
     * @return self
     */
    public function whereNotEmpty($field)
    {
        return $this->where('$nor', [
            [$field => null],
            [$field => ''],
            [$field => []],
            [$field => ['$exists' => false]]
        ]);
    }

    /**
     * Performs whereGreater value search.
     * @param string $field
     * @param mixed $value
     *
     * @return self
     */
    public function whereGreater($field, $value)
    {
        return $this->where($field, ['$gt' => $value]);
    }

    /**
     * Performs whereGreaterOrEqual value search.
     * @param string $field
     * @param mixed $value
     *
     * @return self
     */
    public function whereGreaterOrEqual($field, $value)
    {
        return $this->where($field, ['$gte' => $value]);
    }

    /**
     * Performs whereLess value search.
     * @param string $field
     * @param mixed $value
     *
     * @return self
     */
    public function whereLess($field, $value)
    {
        return $this->where($field, ['$lt' => $value]);
    }

    /**
     * Performs whereLessOrEqual value search.
     * @param string $field
     * @param mixed $value
     *
     * @return self
     */
    public function whereLessOrEqual($field, $value)
    {
        return $this->where($field, ['$lte' => $value]);
    }

    /**
     * Performs whereNotEqual value search.
     * @param string $field
     * @param mixed $value
     *
     * @return self
     */
    public function whereNotEqual($field, $value)
    {
        return $this->where($field, ['$ne' => $value]);
    }

    /**
     * Selects the documents where the value of a
     * field equals any value in the specified array.
     *
     * @param string $field
     * @param array $values
     * @return \Expression
     */
    public function whereIn($field, array $values)
    {
        return $this->where($field, ['$in' => $values]);
    }

    /**
     * Performs whereNotIn values search.
     * @param string $field
     * @param array $values
     *
     * @return self
     */
    public function whereNotIn($field, array $values)
    {
        return $this->where($field, ['$nin' => $values]);
    }

    /**
     * Performs whereExists search.
     * @param string $field
     *
     * @return self
     */
    public function whereExists($field)
    {
        return $this->where($field, ['$exists' => true]);
    }

    /**
     * Performs whereNotExists search.
     * @param string $field
     *
     * @return self
     */
    public function whereNotExists($field)
    {
        return $this->where($field, ['$exists' => false]);
    }

    /**
     * Performs whereHasType search.
     * @see API\Storage\Adapter\Mongo\FieldType
     *
     * @param string $field
     * @param int $type
     *
     * @return self
     */
    public function whereHasType($field, $type)
    {
        return $this->where($field, ['$type' => (int) $type]);
    }

    ////
    // where query: field types
    ////

    /**
     * Performs whereDouble search.
     * @param string $field
     *
     * @return self
     */
    public function whereDouble($field)
    {
        return $this->whereHasType($field, FieldType::DOUBLE);
    }

    /**
     * Performs whereString search.
     * @param string $field
     *
     * @return self
     */
    public function whereString($field)
    {
        return $this->whereHasType($field, FieldType::STRING);
    }

    /**
     * Performs whereObject search.
     * @param string $field
     *
     * @return self
     */
    public function whereObject($field)
    {
        return $this->whereHasType($field, FieldType::OBJECT);
    }

    /**
     * Performs whereBoolean search.
     * @param string $field
     *
     * @return self
     */
    public function whereBoolean($field)
    {
        return $this->whereHasType($field, FieldType::BOOLEAN);
    }

    /**
     * Performs whereArray search.
     * @param string $field
     *
     * @return self
     */
    public function whereArray($field)
    {
        return $this->whereJsCondition('Array.isArray(this.' . $field . ')');
    }

    /**
     * Performs whereArrayOfArrays search.
     * @param string $field
     *
     * @return self
     */
    public function whereArrayOfArrays($field)
    {
        return $this->whereHasType($field, FieldType::ARRAY_TYPE);
    }

    /**
     * Performs whereObjectId search.
     * @param string $field
     *
     * @return self
     */
    public function whereObjectId($field)
    {
        return $this->whereHasType($field, FieldType::OBJECT_ID);
    }

    /**
     * Performs whereDate search.
     * @param string $field
     *
     * @return self
     */
    public function whereDate($field)
    {
        return $this->whereHasType($field, FieldType::DATE);
    }

    /**
     * Performs whereNul search.
     * @param string $field
     *
     * @return self
     */
    public function whereNull($field)
    {
        return $this->whereHasType($field, FieldType::NULL);
    }

    /**
     * Performs whereJsCondition search. Find documents with Mongos $where
     * @param Expression $condition
     *
     * @return self
     */
    public function whereJsCondition($condition)
    {
        return $this->where('$where', $condition);
    }

    ////
    // where query: like
    ////

    /**
     * Performs whereLike search. Find documents where the value matches a regex pattern
     * @param string $field point-delimited field name
     * @param string $regex
     * @param bool $caseInsensitive
     *
     * @return self
     */
    public function whereLike($field, $regex, $caseInsensitive = true)
    {
        // Regex
        $expression = [
            '$regex'    => $regex,
        ];

        // Options
        $options = '';

        if ($caseInsensitive) {
            $options .= 'i';
        }

        $expression['$options'] = $options;

        // Query
        return $this->where($field, $expression);
    }

    ////
    // where query: value matches
    ////

    /**
     * Performs whereAll search.Find documents where the value of a field is an array
     * that contains all the specified elements.
     * This is equivalent of logical AND.
     * @see http://docs.mongodb.org/manual/reference/operator/query/all/
     *
     * @param string $field point-delimited field name
     * @param array $values
     *
     * @return self
     */
    public function whereAll($field, array $values)
    {
        return $this->where($field, ['$all' => $values]);
    }

    /**
     * Performs whereNoneOf search. Find documents where the value of a field is an array
     * that contains none of the specified elements.
     * This is equivalent of logical AND.
     * @see http://docs.mongodb.org/manual/reference/operator/query/all/
     *
     * @param string $field point-delimited field name
     * @param array $values
     *
     * @return self
     */
    public function whereNoneOf($field, array $values)
    {
        return $this->where($field, [
            '$not' => [
                '$all' => $values
            ],
        ]);
    }

    /**
     * Performs whereAny search. Find documents where the value of a field is an array
     * that contains any of the specified elements.
     * This is equivalent of logical AND.
     * @param string $field point-delimited field name
     * @param array $values
     *
     * @return self
     */
    public function whereAny($field, array $values)
    {
        return $this->whereIn($field, $values);
    }

    /**
     * Performs whereElemMatch search.Matches documents in a collection that contain an array field with at
     * least one element that matches all the specified query criteria.
     * @param string $field point-delimited field name
     * @param Expression|callable|array $expression
     *
     * @return self
     * @throws AdapterException
     */
    public function whereElemMatch($field, $expression)
    {
        if (is_callable($expression)) {
            $expression = call_user_func($expression, $this->expression());
        }

        if ($expression instanceof Expression) {
            $expression = $expression->toArray();
        } elseif (!is_array($expression)) {
            throw new AdapterException('Wrong expression passed');
        }

        return $this->where($field, ['$elemMatch' => $expression]);
    }

    /**
     * Performs whereElemNotMatch search. Matches documents in a collection that contain an array field with elements
     * that do not matches all the specified query criteria.
     * @param type $field
     * @param Expression|callable|array $expression
     *
     * @return self
     */
    public function whereElemNotMatch($field, $expression)
    {
        return $this->whereNot($this->expression()->whereElemMatch($field, $expression));
    }

    /**
     * Performs whereArraySize search. Selects documents if the array field is a specified size.
     * @param string $field
     * @param integer $length
     *
     * @return self
     */
    public function whereArraySize($field, $length)
    {
        return $this->where($field, ['$size' => (int) $length]);
    }

    ////
    // where query: logical
    ////

    /**
     * Performs whereOr search. Selects the documents that satisfy at least one of the expressions
     * @param array|Expression $expressions Array of Expression instances
     *
     * @return self
     */
    public function whereOr($expressions = null)
    {
        if ($expressions instanceof Expression) {
            $expressions = func_get_args();
        }

        return $this->where('$or', array_map(function (Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }

    /**
     * Performs whereAnd search. Select the documents that satisfy all the expressions in the array
     * @param array|Expression $expressions Array of Expression instances
     *
     * @return self
     */
    public function whereAnd($expressions = null)
    {
        if ($expressions instanceof Expression) {
            $expressions = func_get_args();
        }

        return $this->where('$and', array_map(function (Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }

    /**
     * Performs whereNor search. Selects the documents that fail all the query expressions in the array
     * @param array[Expression]$expressions Array of Expression instances
     *
     * @return self
     */
    public function whereNor($expressions = null)
    {
        if ($expressions instanceof Expression) {
            $expressions = func_get_args();
        }

        return $this->where('$nor', array_map(function (Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }

    /**
     * Performs where search
     * @param Expression $expression
     *
     * @return self
     */
    public function whereNot(Expression $expression)
    {
        foreach ($expression->toArray() as $field => $value) {
            // $not acceptable only for operators-expressions
            if (is_array($value) && is_string(key($value))) {
                $this->where($field, ['$not' => $value]);
            }
            // for single values use $ne
            else {
                $this->whereNotEqual($field, $value);
            }
        }

        return $this;
    }

    /**
     * Select documents where the value of a field divided by a divisor has the specified remainder (i.e. perform a modulo operation to select documents)
     * @param string $field
     * @param int $divisor
     * @param int $remainder
     *
     * @return self
     */
    public function whereMod($field, $divisor, $remainder)
    {
        $this->where($field, array(
            '$mod' => [(int) $divisor, (int) $remainder],
        ));

        return $this;
    }

    /**
     * Perform fulltext search
     *
     * @link https://docs.mongodb.org/manual/reference/operator/query/text/
     * @link https://docs.mongodb.org/manual/tutorial/specify-language-for-text-index/
     *
     * If a collection contains documents or embedded documents that are in different languages,
     * include a field named language in the documents or embedded documents and specify as its value the language
     * for that document or embedded document.
     *
     * The specified language in the document overrides the default language for the text index.
     * The specified language in an embedded document override the language specified in an enclosing document or
     * the default language for the index.
     *
     * Case Insensitivity:
     * @link https://docs.mongodb.org/manual/reference/operator/query/text/#text-operator-case-sensitivity
     *
     * Diacritic Insensitivity:
     * @link https://docs.mongodb.org/manual/reference/operator/query/text/#text-operator-diacritic-sensitivity
     *
     * @param $search A string of terms that MongoDB parses and uses to query the text index. MongoDB performs a
     *  logical OR search of the terms unless specified as a phrase.
     * @param $language Optional. The language that determines the list of stop words for the search and the
     *  rules for the stemmer and tokenizer. If not specified, the search uses the default language of the index.
     *  If you specify a language value of "none", then the text search uses simple tokenization
     *  with no list of stop words and no stemming.
     * @param bool|false $caseSensitive Allowed from v.3.2 A boolean flag to enable or disable case
     *  sensitive search. Defaults to false; i.e. the search defers to the case insensitivity of the text index.
     * @param bool|false $diacriticSensitive Allowed from v.3.2 A boolean flag to enable or disable diacritic
     *  sensitive search against version 3 text indexes. Defaults to false; i.e. the search defers to the diacritic
     *  insensitivity of the text index. Text searches against earlier versions of the text index are inherently
     *  diacritic sensitive and cannot be diacritic insensitive. As such, the $diacriticSensitive option has no
     *  effect with earlier versions of the text index.
     *
     * @return self
     */
    public function whereText(
        $search,
        $language = null,
        $caseSensitive = null,
        $diacriticSensitive = null
    ) {
        $this->expression['$text'] = [
            '$search' => $search,
        ];

        if ($language) {
            $this->expression['$text']['$language'] = $language;
        }

        // Version 3.2 feature
        if ($caseSensitive) {
            $this->expression['$text']['$caseSensitive'] = (bool) $caseSensitive;
        }

        // Version 3.2 feature
        if ($diacriticSensitive) {
            $this->expression['$text']['$diacriticSensitive'] = (bool) $diacriticSensitive;
        }

        return $this;
    }
}
