<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2015 Brightcookie Pty Ltd
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

namespace API\Service;

use API\Service;
use MongoDate;
use API\Resource;
use API\Util;
use Slim\Helper\Set;
use Sokil\Mongo\Cursor;

class Statement extends Service
{
    /**
     * Statements.
     *
     * @var array
     */
    protected $statements;

    /**
     * Attachments.
     *
     * @var array
     */
    protected $attachments;

    /**
     * The limit associated with the document query.
     *
     * @var int
     */
    protected $limit;

    /**
     * Format associated with the query.
     *
     * @var string
     */
    protected $format;

    /**
     * Descending order associated with the query.
     *
     * @var bool
     */
    protected $descending;

    /**
     * Cursor.
     *
     * @var cursor
     */
    protected $cursor;

    /**
     * Is this a single statement fetch?
     *
     * @var bool
     */
    protected $single = false;

    /**
     * Is this a single statement match?
     *
     * @var bool
     */
    protected $match = false;

    /**
     * Provide a statement count.
     *
     * @var int
     */
    protected $count;

    /**
     * Fetches statements according to the given parameters.
     *
     * @param array $request The HTTP request object.
     *
     * @return array An array of statement objects.
     */
    public function statementGet($request)
    {
        $params = new Set($request->get());

        $collection  = $this->getDocumentManager()->getCollection('statements');
        $cursor      = $collection->find();

        // Single statement
        if ($params->has('statementId')) {
            $cursor->where('statement.id', $params->get('statementId'));
            $cursor->where('voided', false);

            if ($cursor->count() === 0) {
                throw new Exception('Statement does not exist.', Resource::STATUS_NOT_FOUND);
            }

            $this->cursor   = $cursor;
            $this->single = true;

            return $this;
        }

        if ($params->has('voidedStatementId')) {
            $cursor->where('statement.id', $params->get('voidedStatementId'));
            $cursor->where('voided', true);

            if ($cursor->count() === 0) {
                throw new Exception('Statement does not exist.', Resource::STATUS_NOT_FOUND);
            }

            $this->cursor   = $cursor;
            $this->single = true;

            return $this;
        }

        $cursor->where('voided', false);

        // Multiple statements
        if ($params->has('agent')) {
            $agent = $params->get('agent');
            $agent = json_decode($agent, true);
            //Fetch the identifier - otherwise we'd have to order the JSON
            if (isset($agent['mbox'])) {
                $uniqueIdentifier = 'mbox';
            } elseif (isset($agent['mbox_sha1sum'])) {
                $uniqueIdentifier = 'mbox_sha1sum';
            } elseif (isset($agent['openid'])) {
                $uniqueIdentifier = 'openid';
            } elseif (isset($agent['account'])) {
                $uniqueIdentifier = 'account';
            }
            if ($params->has('related_agents') && $params->get('related_agents') === 'true') { 
                if ($uniqueIdentifier === 'account') {
                    $cursor->whereAnd(
                        $collection->expression()->whereOr(
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.actor.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.actor.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.object.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.object.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.authority.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.authority.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.context.team.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.context.team.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.context.instructor.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.context.instructor.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.object.objectType', 'SubStatement'),
                                $collection->expression()->where('statement.object.object.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.object.object.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.actor.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.actor.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.object.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.object.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.authority.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.authority.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.context.team.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.context.team.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.context.instructor.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.context.instructor.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.object.objectType', 'SubStatement'),
                                $collection->expression()->where('references.object.object.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.object.object.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            )
                        )
                    );
                } else {
                    $cursor->whereAnd(
                        $collection->expression()->whereOr(
                            $collection->expression()->where('statement.actor.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('statement.object.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('statement.authority.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('statement.context.team.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('statement.context.instructor.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.object.objectType', 'SubStatement'),
                                $collection->expression()->where('statement.object.object.'.$uniqueIdentifier, $agent[$uniqueIdentifier])
                            ),
                            $collection->expression()->where('references.actor.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('references.object.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('references.authority.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('references.context.team.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('references.context.instructor.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.object.objectType', 'SubStatement'),
                                $collection->expression()->where('references.object.object.'.$uniqueIdentifier, $agent[$uniqueIdentifier])
                            )
                        )
                    );
                }
            } else {
                if ($uniqueIdentifier === 'account') {
                    $cursor->whereAnd(
                        $collection->expression()->whereOr(
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.actor.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.actor.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('statement.object.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('statement.object.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.actor.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.actor.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            ),
                            $collection->expression()->whereAnd(
                                $collection->expression()->where('references.object.'.$uniqueIdentifier.'.homePage', $agent[$uniqueIdentifier]['homePage']),
                                $collection->expression()->where('references.object.'.$uniqueIdentifier.'.name', $agent[$uniqueIdentifier]['name'])
                            )
                        )
                    );
                } else {
                    $cursor->whereAnd(
                        $collection->expression()->whereOr(
                            $collection->expression()->where('statement.actor.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('statement.object.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('references.actor.'.$uniqueIdentifier, $agent[$uniqueIdentifier]),
                            $collection->expression()->where('references.object.'.$uniqueIdentifier, $agent[$uniqueIdentifier])
                        )
                    );
                }
            }
        }
        if ($params->has('verb')) {
            $cursor->whereAnd(
                $collection->expression()->whereOr(
                    $collection->expression()->where('statement.verb.id', $params->get('verb')),
                    $collection->expression()->where('references.verb.id', $params->get('verb'))
                )
            );
        }
        if ($params->has('activity')) {
            // Handle related
            if ($params->has('related_activities') && $params->get('related_activities') === 'true') {
                $cursor->whereAnd(
                    $collection->expression()->whereOr(
                        $collection->expression()->where('statement.object.id', $params->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.parent.id', $params->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.category.id', $params->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.grouping.id', $params->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.other.id', $params->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.parent.id', $params->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.parent.id', $params->get('activity')),
                        $collection->expression()->whereAnd(
                            $collection->expression()->where('statement.object.objectType', 'SubStatement'),
                            $collection->expression()->where('statement.object.object', $params->get('activity'))
                        ),
                        $collection->expression()->where('references.object.id', $params->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.parent.id', $params->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.category.id', $params->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.grouping.id', $params->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.other.id', $params->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.parent.id', $params->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.parent.id', $params->get('activity')),
                        $collection->expression()->whereAnd(
                            $collection->expression()->where('references.object.objectType', 'SubStatement'),
                            $collection->expression()->where('references.object.object', $params->get('activity'))
                        )
                    )
                );
            } else {
                $cursor->whereAnd(
                    $collection->expression()->whereOr(
                        $collection->expression()->where('statement.object.id', $params->get('activity')),
                        $collection->expression()->where('references.object.id', $params->get('activity'))
                    )
                );
            }
        }

        if ($params->has('registration')) {
            $cursor->whereAnd(
                $collection->expression()->whereOr(
                    $collection->expression()->where('statement.context.registration', $params->get('registration')),
                    $collection->expression()->where('references.context.registration', $params->get('registration'))
                )
            );
        }

        // Date based filters
        if ($params->has('since')) {
            $since = Util\Date::dateStringToMongoDate($params->get('since'));
            $cursor->whereGreaterOrEqual('mongo_timestamp', $since);
        }

        if ($params->has('until')) {
            $until = Util\Date::dateStringToMongoDate($params->get('until'));
            $cursor->whereLessOrEqual('mongo_timestamp', $until);
        }

        // Count before paginating
        $this->count = $cursor->count();

        // Handle pagination
        if ($params->has('since_id')) {
            $id = new \MongoId($params->get('since_id'));
            $cursor->whereGreaterOrEqual('_id', $id);
        }

        if ($params->has('until_id')) {
            $id = new \MongoId($params->get('until_id'));
            $cursor->whereLessOrEqual('_id', $id);
        }

        $this->format = $this->getSlim()->config('xAPI')['default_statement_get_format'];
        if ($params->has('format')) {
            $this->format = $params->get('format');
        }

        $this->descending = true;
        $cursor->sort(['_id' => -1]);
        if ($params->has('ascending')) {
            $asc = $params->get('ascending');
            if(strtolower($asc) === 'true' || $asc === '1') {
                $cursor->sort(['_id' => 1]);
                $this->descending = false;
            }
        }

        if ($params->has('limit') && $params->get('limit') < $this->getSlim()->config('xAPI')['statement_get_limit'] && $params->get('limit') > 0) {
            $limit = $params->get('limit');
        } else {
            $limit = $this->getSlim()->config('xAPI')['statement_get_limit'];
        }
        // Hackish solution...think of a different way for handling this
        $limit = $limit + 1;

        $this->limit = $limit;
        $cursor->limit($limit);

        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Tries to  a statement with a specified statementId.
     *
     * @return array An array of statement documents or a single statement document.
     */
    public function statementPost($request)
    {
        // Check for multipart request
        if ($request->isMultipart()) {
            $jsonRequest = $request->parts()->get(0);
        } else {
            $jsonRequest = $request;
        }

        // TODO: Move header validation in json-schema as well
        if ($jsonRequest->getMediaType() !== 'application/json') {
            throw new \Exception('Media type specified in Content-Type header must be \'application/json\'!', Resource::STATUS_BAD_REQUEST);
        }

        // Validation has been completed already - everyhing is assumed to be valid
        $body = $jsonRequest->getBody();
        $body = json_decode($body, true);

        // Some clients escape the JSON - handle them
        if (is_string($body)) {
            $body = json_decode($body, true);
        }

        $collection  = $this->getDocumentManager()->getCollection('statements');
        $activityCollection  = $this->getDocumentManager()->getCollection('activities');

        // Save attachments - this could be in a queue perhaps...
        if ($request->isMultipart()) {
            $fsAdapter = \API\Util\Filesystem::generateAdapter($this->getSlim()->config('filesystem'));

            $attachmentCollection = $this->getDocumentManager()->getCollection('attachments');

            $partCount = $request->parts()->count();

            for ($i = 1; $i < $partCount; $i++) {
                $part           = $request->parts()->get($i);

                $attachmentBody = $part->getBody();

                $detectedEncoding = mb_detect_encoding($attachmentBody);
                $contentEncoding = $part->headers('Content-Transfer-Encoding');

                if ($detectedEncoding === 'UTF-8' && ($contentEncoding === null || $contentEncoding === 'binary')) {
                    try {
                        $attachmentBody = iconv('UTF-8', 'ISO-8859-1//IGNORE', $attachmentBody);
                    } catch (\Exception $e) {
                        //Use raw file on failed conversion (do nothing!)
                    }
                }

                $hash           = $part->headers('X-Experience-API-Hash');
                $contentType    = $part->headers('Content-Type');

                $attachmentDocument = $attachmentCollection->createDocument();
                $attachmentDocument->setSha2($hash);
                $attachmentDocument->setContentType($contentType);
                $attachmentDocument->setTimestamp(new MongoDate());
                $attachmentDocument->save();

                $fsAdapter->put($hash, $attachmentBody);
            }
        }

        $attachmentBase = $this->getSlim()->url->getBaseUrl().$this->getSlim()->config('filesystem')['exposed_url'];

        // Multiple statements
        if ($this->areMultipleStatements($body)) {
            $statements = [];
            foreach ($body as $statement) {
                $statementDocument = $collection->createDocument();
                // Overwrite authority - unless it's a super token and manual authority is set
                if (!($this->getAccessToken()->isSuperToken() && isset($statement['authority'])) || !isset($statement['authority'])) {
                    $statement['authority'] = $this->getAccessToken()->generateAuthority();
                }
                $statementDocument->setStatement($statement);
                // Dates
                $currentDate = Util\Date::dateTimeExact();
                $statementDocument->setStored(Util\Date::dateTimeToISO8601($currentDate));
                $statementDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
                $statementDocument->setDefaultTimestamp();
                $statementDocument->fixAttachmentLinks($attachmentBase);
                $statementDocument->setDefaultId();
                if ($statementDocument->isReferencing()) {
                    // Copy values of referenced statement chain inside current statement for faster query-ing
                    // (space-time tradeoff)
                    $referencedStatement = $statementDocument->getReferencedStatement();

                    $existingReferences = [];
                    if (null !== $referencedStatement->getReferences()) {
                        $existingReferences = $referencedStatement->getReferences();
                    }
                    $existingReferences[] = $referencedStatement->getStatement();
                    $statementDocument->setReferences($existingReferences);
                }
                $statements[] = $statementDocument->toArray();
                $this->statements[] = $statementDocument;
                if ($statementDocument->isVoiding()) {
                    $referencedStatement = $statementDocument->getReferencedStatement();
                    if (!$referencedStatement->isVoiding()) {
                        $referencedStatement->setVoided(true);
                        $referencedStatement->save();
                    } else {
                        throw new \Exception('Voiding statements cannot be voided.', Resource::STATUS_CONFLICT);
                    }
                }
                if ($this->getAccessToken()->hasPermission('define')) {
                    $activities = $statementDocument->extractActivities();
                    if (count($activities) > 0) {
                        $activityCollection->insertMultiple($activities);
                    }
                }
                // Save statement
                $statementDocument->save();

                // Add to log
                $this->getSlim()->requestLog->addRelation('statements', $statementDocument)->save();
            }
            // $collection->insertMultiple($statements); // Batch operation is much faster ~600%
            // However, because we add every single statement to the access log, we can't use it
            // The only way to still use (fast) batch inserts would be to move the attachment of
            // statements to their respective log entries in a async queue!
        } else {
            $statementDocument = $collection->createDocument();
            // Overwrite authority - unless it's a super token and manual authority is set
            if (!($this->getAccessToken()->isSuperToken() && isset($statement['authority'])) || !isset($statement['authority'])) {
                $statement['authority'] = $this->getAccessToken()->generateAuthority();
            }
            $statementDocument->setStatement($body);
            // Dates
            $currentDate = Util\Date::dateTimeExact();
            $statementDocument->setStored(Util\Date::dateTimeToISO8601($currentDate));
            $statementDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
            $statementDocument->setDefaultTimestamp();
            $statementDocument->fixAttachmentLinks($attachmentBase);
            $statementDocument->setDefaultId();

            if ($statementDocument->isReferencing()) {
                // Copy values of referenced statement chain inside current statement for faster query-ing
                // (space-time tradeoff)
                $referencedStatement = $statementDocument->getReferencedStatement();

                $existingReferences = [];
                if (null !== $referencedStatement->getReferences()) {
                    $existingReferences = $referencedStatement->getReferences();
                }
                $existingReferences[] = $referencedStatement->getStatement();

                $statementDocument->setReferences($existingReferences);
            }

            if ($statementDocument->isVoiding()) {
                $referencedStatement = $statementDocument->getReferencedStatement();
                if (!$referencedStatement->isVoiding()) {
                    $referencedStatement->setVoided(true);
                    $referencedStatement->save();
                } else {
                    throw new \Exception('Voiding statements cannot be voided.', Resource::STATUS_CONFLICT);
                }
            }

            if ($this->getAccessToken()->hasPermission('define')) {
                $activities = $statementDocument->extractActivities();
                if (count($activities) > 0) {
                    $activityCollection->insertMultiple($activities);
                }
            }

            $statementDocument->save();

            // Add to log
            $this->getSlim()->requestLog->addRelation('statements', $statementDocument)->save();

            $this->single = true;
            $this->statements = [$statementDocument];
        }

        return $this;
    }

    /**
     * Tries to PUT a statement with a specified statementId.
     *
     * @return
     */
    public function statementPut($request)
    {
        // Check for multipart request
        if ($request->isMultipart()) {
            $jsonRequest = $request->parts()->get(0);
        } else {
            $jsonRequest = $request;
        }


        // Validation has been completed already - everyhing is assumed to be valid (from an external view!)
        // TODO: Move header validation in json-schema as well
        if ($jsonRequest->getMediaType() !== 'application/json') {
            throw new \Exception('Media type specified in Content-Type header must be \'application/json\'!', Resource::STATUS_BAD_REQUEST);
        }

        // Validation has been completed already - everyhing is assumed to be valid
        $body = $jsonRequest->getBody();
        $body = json_decode($body, true);

        // Some clients escape the JSON - handle them
        if (is_string($body)) {
            $body = json_decode($body, true);
        }

        // Save attachments - this could be in a queue perhaps...
        if ($request->isMultipart()) {
            $fsAdapter = \API\Util\Filesystem::generateAdapter($this->getSlim()->config('filesystem'));

            $attachmentCollection = $this->getDocumentManager()->getCollection('attachments');

            $partCount = $request->parts()->count();

            for ($i = 1; $i < $partCount; $i++) {
                $part           = $request->parts()->get($i);

                $attachmentBody = $part->getBody();

                $detectedEncoding = mb_detect_encoding($attachmentBody);
                $contentEncoding = $part->headers('Content-Transfer-Encoding');

                if ($detectedEncoding === 'UTF-8' && ($contentEncoding === null || $contentEncoding === 'binary')) {
                    try {
                        $attachmentBody = iconv('UTF-8', 'ISO-8859-1//IGNORE', $attachmentBody);
                    } catch (\Exception $e) {
                        //Use raw file on failed conversion (do nothing!)
                    }
                }

                $hash           = $part->headers('X-Experience-API-Hash');
                $contentType    = $part->headers('Content-Type');

                $attachmentDocument = $attachmentCollection->createDocument();
                $attachmentDocument->setSha2($hash);
                $attachmentDocument->setContentType($contentType);
                $attachmentDocument->setTimestamp(new MongoDate());
                $attachmentDocument->save();

                $fsAdapter->put($hash, $attachmentBody);
            }
        }

        $attachmentBase = $this->getSlim()->url->getBaseUrl().$this->getSlim()->config('filesystem')['exposed_url'];


        // Single
        $params = new Set($request->get());

        $activityCollection  = $this->getDocumentManager()->getCollection('activities');
        $collection          = $this->getDocumentManager()->getCollection('statements');
        $cursor              = $collection->find();

        // Single statement
        $cursor->where('statement.id', $params->get('statementId'));
        $result = $cursor->findOne();

        // ID exists, check if different or conflict
        if ($result) {
            // Same - return 204 No content
            if ($body === $result) {
                $this->match = true;
            } else { // Mismatch - return 409 Conflict
                throw new Exception('An existing statement already exists with the same ID and is different from the one provided.', Resource::STATUS_CONFLICT);
            }
        } else { // Store new statement
            $statementDocument = $collection->createDocument();
            // Overwrite authority - unless it's a super token and manual authority is set
            if (!($this->getAccessToken()->isSuperToken() && isset($statement['authority'])) || !isset($statement['authority'])) {
                $statement['authority'] = $this->getAccessToken()->generateAuthority();
            }
            // Check statementId
            if (isset($body['id'])) {
                //Check for match
                if ($body['id'] !== $params->get('statementId')) {
                    throw new \Exception('Statement ID query parameter doesn\'t match the given statement property', Resource::STATUS_BAD_REQUEST);
                }
            } else {
                $body['id'] = $params->get('statementId');
            }
            // Set the statement
            $statementDocument->setStatement($body);
            // Dates
            $currentDate = Util\Date::dateTimeExact();
            $statementDocument->setStored(Util\Date::dateTimeToISO8601($currentDate));
            $statementDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
            $statementDocument->setDefaultTimestamp();
            $statementDocument->fixAttachmentLinks($attachmentBase);

            if ($statementDocument->isReferencing()) {
                // Copy values of referenced statement chain inside current statement for faster query-ing
                // (space-time tradeoff)
                $referencedStatement = $statementDocument->getReferencedStatement();

                $existingReferences = [];
                if (null !== $referencedStatement->getReferences()) {
                    $existingReferences = $referencedStatement->getReferences();
                }
                $statementDocument->setReferences(array_push($existingReferences, $referencedStatement->getStatement()));
            }

            if ($statementDocument->isVoiding()) {
                $referencedStatement = $statementDocument->getReferencedStatement();
                if (!$referencedStatement->isVoiding()) {
                    $referencedStatement->setVoided(true);
                    $referencedStatement->save();
                } else {
                    throw new \Exception('Voiding statements cannot be voided.', Resource::STATUS_CONFLICT);
                }
            }

            if ($this->getAccessToken()->hasPermission('define')) {
                $activities = $statementDocument->extractActivities();
                if (count($activities) > 0) {
                    $activityCollection->insertMultiple($activities);
                }
            }

            $statementDocument->save();

            // Add to log
            $this->getSlim()->requestLog->addRelation('statements', $statementDocument)->save();

            $this->single = true;
            $this->statements = [$statementDocument];
        }

        return $this;
    }

    /**
     * Gets the Statements.
     *
     * @return array
     */
    public function getStatements()
    {
        return $this->statements;
    }

    /**
     * Sets the Statements.
     *
     * @param array $statements the statements
     *
     * @return self
     */
    public function setStatements(array $statements)
    {
        $this->statements = $statements;

        return $this;
    }

    /**
     * Gets the Attachments.
     *
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Sets the Attachments.
     *
     * @param array $attachments the attachments
     *
     * @return self
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Gets the The limit associated with the document query.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Sets the The limit associated with the document query.
     *
     * @param int $limit the limit
     *
     * @return self
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Gets the Format associated with the query.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the Format associated with the query.
     *
     * @param string $format the format
     *
     * @return self
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Gets the Descending order associated with the query.
     *
     * @return bool
     */
    public function getDescending()
    {
        return $this->descending;
    }

    /**
     * Sets the Descending order associated with the query.
     *
     * @param bool $descending the descending
     *
     * @return self
     */
    public function setDescending($descending)
    {
        $this->descending = $descending;

        return $this;
    }

    /**
     * Gets the Cursor.
     *
     * @return cursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Sets the Cursor.
     *
     * @param cursor $cursor the cursor
     *
     * @return self
     */
    public function setCursor(Cursor $cursor)
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Gets the Is this a single statement fetch?.
     *
     * @return bool
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Sets the Is this a single statement fetch?.
     *
     * @param bool $single the is single
     *
     * @return self
     */
    public function setSingle($single)
    {
        $this->single = $single;

        return $this;
    }

    // Quickest solution for checking 1D vs 2D assoc arrays
    private function areMultipleStatements(&$array)
    {
        return ($array === array_values($array));
    }

    /**
     * Gets the Is this a single statement match?.
     *
     * @return bool
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * Sets the Is this a single statement match?.
     *
     * @param bool $match the is match
     *
     * @return self
     */
    public function setMatch($match)
    {
        $this->match = $match;

        return $this;
    }

    /**
     * Gets the Access token to check for permissions.
     *
     * @return API\Document\Auth\AbstractToken
     */
    public function getAccessToken()
    {
        return $this->getSlim()->auth;
    }

    /**
     * Gets the Provide a statement count.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Sets the Provide a statement count.
     *
     * @param int $count the count
     *
     * @return self
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }
}
