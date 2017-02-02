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
use Exception;

interface ExpressionInterface
{
    /**
     * Create new instance of expression
     * @return \Expression
     */
    public function expression();
    
    /**
     * Return a expression
     * @return \Cursor|\Expression
     */
    public function where($field, $value);

    public function whereEmpty($field);

    public function whereNotEmpty($field);

    public function whereGreater($field, $value);

    public function whereGreaterOrEqual($field, $value);

    public function whereLess($field, $value);

    public function whereLessOrEqual($field, $value);

    public function whereNotEqual($field, $value);

    /**
     * Selects the documents where the value of a
     * field equals any value in the specified array.
     *
     * @param string $field
     * @param array $values
     * @return \Expression
     */
    public function whereIn($field, array $values);

    public function whereNotIn($field, array $values);

    public function whereExists($field);

    public function whereNotExists($field);

    public function whereHasType($field, $type);

    public function whereDouble($field);

    public function whereString($field);

    public function whereObject($field);

    public function whereBoolean($field);

    public function whereArray($field);

    public function whereArrayOfArrays($field);

    public function whereObjectId($field);

    public function whereDate($field);

    public function whereNull($field);

    public function whereJsCondition($condition);

    public function whereLike($field, $regex, $caseInsensitive = true);

    /**
     * Find documents where the value of a field is an array
     * that contains all the specified elements.
     * This is equivalent of logical AND.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/query/all/
     *
     * @param string $field point-delimited field name
     * @param array $values
     * @return \Expression
     */
    public function whereAll($field, array $values);

    /**
     * Find documents where the value of a field is an array
     * that contains none of the specified elements.
     * This is equivalent of logical AND.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/query/all/
     *
     * @param string $field point-delimited field name
     * @param array $values
     * @return \Expression
     */
    public function whereNoneOf($field, array $values);

    /**
     * Find documents where the value of a field is an array
     * that contains any of the specified elements.
     * This is equivalent of logical AND.
     *
     * @param string $field point-delimited field name
     * @param array $values
     * @return \Expression
     */
    public function whereAny($field, array $values);

    /**
     * Matches documents in a collection that contain an array field with at
     * least one element that matches all the specified query criteria.
     *
     * @param string $field point-delimited field name
     * @param \Expression|callable|array $expression
     * @return \Expression
     */
    public function whereElemMatch($field, $expression);

    /**
     * Matches documents in a collection that contain an array field with elements
     * that do not matches all the specified query criteria.
     *
     * @param type $field
     * @param \Expression|callable|array $expression
     * @return \Expression
     */
    public function whereElemNotMatch($field, $expression);

    /**
     * Selects documents if the array field is a specified size.
     *
     * @param string $field
     * @param integer $length
     * @return \Expression
     */
    public function whereArraySize($field, $length);

    /**
     * Selects the documents that satisfy at least one of the expressions
     *
     * @param array|\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return \Expression
     */
    public function whereOr($expressions = null);

    /**
     * Select the documents that satisfy all the expressions in the array
     *
     * @param array|\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return \Expression
     */
    public function whereAnd($expressions = null);

    /**
     * Selects the documents that fail all the query expressions in the array
     *
     * @param array|\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return \Expression
     */
    public function whereNor($expressions = null);

    public function whereNot(Expression $expression);

    /**
     * Select documents where the value of a field divided by a divisor has the specified remainder (i.e. perform a modulo operation to select documents)
     *
     * @param string $field
     * @param int $divisor
     * @param int $remainder
     */
    public function whereMod($field, $divisor, $remainder);

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
     * @return $this
     */
    public function whereText(
        $search,
        $language = null,
        $caseSensitive = null,
        $diacriticSensitive = null
    );

    public function toArray();

    public function merge(Expression $expression);

    /**
     * Transform expression in different formats to canonical array form
     *
     * @param mixed $mixed
     * @return array
     * @throws \Exception
     */
    public static function convertToArray($mixed);
}