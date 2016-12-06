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

namespace API\Storage\Adapter\MongoLegacy;

use InvalidArgumentException;
use API\Resource;
use API\Storage\Query\StatementResult;
use API\Storage\Query\StatementInterface;
use API\Storage\Adapter\Base;

class Statement extends Base implements StatementInterface
{
	/**
	 * @param  $parameters parameters as per xAPI spec
	 * @return StatementResult object
	 */
	public function getStatementsFiltered($parameters)
	{
		$collection  = $this->getDocumentManager()->getCollection('statements');
        $cursor      = $collection->find();

        // Single statement
        if ($parameters->has('statementId')) {
            $cursor->where('statement.id', $parameters->get('statementId'));
            $cursor->where('voided', false);

            if(!Uuid::isValid($parameters->get('statementId'))){
                throw new Exception('Not a valid uuid.', Resource::STATUS_NOT_FOUND);
            }
            if ($cursor->count() === 0) {
                throw new Exception('Statement does not exist.', Resource::STATUS_NOT_FOUND);
            }

            $statementResult = new StatementResult();
            $statementResult->setStatementCursor($cursor);
            $statementResult->setCurrentCount(1);
            $statementResult->setHasMore(false);
            $statementResult->setSingleStatementRequest(true);

            return $statementResult;
        }

        if ($parameters->has('voidedStatementId')) {
            $cursor->where('statement.id', $parameters->get('voidedStatementId'));
            $cursor->where('voided', true);

            if ($cursor->count() === 0) {
                throw new Exception('Statement does not exist.', Resource::STATUS_NOT_FOUND);
            }

            $statementResult = new StatementResult();
            $statementResult->setStatementCursor($cursor);
            $statementResult->setCurrentCount(1);
            $statementResult->setHasMore(false);
            $statementResult->setSingleStatementRequest(true);

            return $statementResult;
        }

        // New StatementResult for non-single statement queries
        $statementResult = new StatementResult();

        $cursor->where('voided', false);

        // Multiple statements
        if ($parameters->has('agent')) {
            $agent = $parameters->get('agent');
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
            if ($parameters->has('related_agents') && $parameters->get('related_agents') === 'true') {
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
        if ($parameters->has('verb')) {
            $cursor->whereAnd(
                $collection->expression()->whereOr(
                    $collection->expression()->where('statement.verb.id', $parameters->get('verb')),
                    $collection->expression()->where('references.verb.id', $parameters->get('verb'))
                )
            );
        }
        if ($parameters->has('activity')) {
            // Handle related
            if ($parameters->has('related_activities') && $parameters->get('related_activities') === 'true') {
                $cursor->whereAnd(
                    $collection->expression()->whereOr(
                        $collection->expression()->where('statement.object.id', $parameters->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.parent.id', $parameters->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.category.id', $parameters->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.grouping.id', $parameters->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.other.id', $parameters->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.parent.id', $parameters->get('activity')),
                        $collection->expression()->where('statement.context.contextActivities.parent.id', $parameters->get('activity')),
                        $collection->expression()->whereAnd(
                            $collection->expression()->where('statement.object.objectType', 'SubStatement'),
                            $collection->expression()->where('statement.object.object', $parameters->get('activity'))
                        ),
                        $collection->expression()->where('references.object.id', $parameters->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.parent.id', $parameters->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.category.id', $parameters->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.grouping.id', $parameters->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.other.id', $parameters->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.parent.id', $parameters->get('activity')),
                        $collection->expression()->where('references.context.contextActivities.parent.id', $parameters->get('activity')),
                        $collection->expression()->whereAnd(
                            $collection->expression()->where('references.object.objectType', 'SubStatement'),
                            $collection->expression()->where('references.object.object', $parameters->get('activity'))
                        )
                    )
                );
            } else {
                $cursor->whereAnd(
                    $collection->expression()->whereOr(
                        $collection->expression()->where('statement.object.id', $parameters->get('activity')),
                        $collection->expression()->where('references.object.id', $parameters->get('activity'))
                    )
                );
            }
        }

        if ($parameters->has('registration')) {
            $cursor->whereAnd(
                $collection->expression()->whereOr(
                    $collection->expression()->where('statement.context.registration', $parameters->get('registration')),
                    $collection->expression()->where('references.context.registration', $parameters->get('registration'))
                )
            );
        }

        // Date based filters
        if ($parameters->has('since')) {
            $since = Util\Date::dateStringToMongoDate($parameters->get('since'));
            $cursor->whereGreaterOrEqual('mongo_timestamp', $since);
        }

        if ($parameters->has('until')) {
            $until = Util\Date::dateStringToMongoDate($parameters->get('until'));
            $cursor->whereLessOrEqual('mongo_timestamp', $until);
        }

        // Count before paginating
        $statementResult->setTotalCount($cursor->count());

        // Handle pagination
        if ($parameters->has('since_id')) {
            $id = new \MongoId($parameters->get('since_id'));
            $cursor->whereGreaterOrEqual('_id', $id);
        }

        if ($parameters->has('until_id')) {
            $id = new \MongoId($parameters->get('until_id'));
            $cursor->whereLessOrEqual('_id', $id);
        }

        $statementResult->setRequestedFormat($this->getSlim()->config('xAPI')['default_statement_get_format']);
        if ($parameters->has('format')) {
            $statementResult->setRequestedFormat($parameters->get('format'));
        }

        $statementResult->setSortDescending(true);
        $statementResult->setSortAscending(false);
        $cursor->sort(['_id' => -1]);
        if ($parameters->has('ascending')) {
            $asc = $parameters->get('ascending');
            if(strtolower($asc) === 'true' || $asc === '1') {
                $cursor->sort(['_id' => 1]);
                $statementResult->setSortDescending(false);
                $statementResult->setSortAscending(true);
            }
        }

        if ($parameters->has('limit') && $parameters->get('limit') < $this->getSlim()->config('xAPI')['statement_get_limit'] && $parameters->get('limit') > 0) {
            $limit = $parameters->get('limit');
        } else {
            $limit = $this->getSlim()->config('xAPI')['statement_get_limit'];
        }

        $cursor->limit($limit);

        // Remaining includes the current page!
        $statementResult->setRemainingCount($cursor->count());

        if ($this->getRemainingCount() > $limit) {
            $statementResult->setHasMore(true);
        } else {
            $statementResult->setHasMore(false);
        }

        $statementResult->setStatementCursor($cursor);

        return $statementResult;
	}

	public function getStatementById($statementId)
    {
        $requestedStatement = $this->getDocumentManager()->getCollection()->find()->where('statement.id', $statementId)->current();

        if (null === $requestedStatement) {
            throw new \InvalidArgumentException('Requested statement does not exist!', Resource::STATUS_BAD_REQUEST);
        }

        return $requestedStatement;
    }

	private function storeStatement($statementObject)
    {
        $collection  = $this->getDocumentManager()->getCollection('statements');
        // TODO: This should be in Activity storage manager!
        $activityCollection  = $this->getDocumentManager()->getCollection('activities');

        $attachmentBase = $this->getSlim()->url->getBaseUrl().$this->getSlim()->config('filesystem')['exposed_url'];

        if (isset($statementObject['id'])) {
            $cursor = $collection->find();
            $cursor->where('statement.id', $statementObject['id']);
            $result = $cursor->findOne();

            // ID exists, check if different or conflict
            if ($result) {
                // Same - return 200
                if ($statement == $result->getStatement()) {
                    // Do nothing
                } else { // Mismatch - return 409 Conflict
                    throw new Exception('An existing statement already exists with the same ID and is different from the one provided.', Resource::STATUS_CONFLICT);
                }
            }
        }

        $statementDocument = $collection->createDocument();
        // Overwrite authority - unless it's a super token and manual authority is set
        if (!($this->getAccessToken()->isSuperToken() && isset($statementObject['authority'])) || !isset($statementObject['authority'])) {
            $statementObject['authority'] = $this->getAccessToken()->generateAuthority();
        }
        $statementDocument->setStatement($statementObject);
        // Dates
        $currentDate = Util\Date::dateTimeExact();
        $statementDocument->setStored(Util\Date::dateTimeToISO8601($currentDate));
        $statementDocument->setMongoTimestamp(Util\Date::dateTimeToMongoDate($currentDate));
        $statementDocument->setDefaultTimestamp();
        $statementDocument->fixAttachmentLinks($attachmentBase);
        $statementDocument->convertExtensionKeysToUnicode();
        $statementDocument->setDefaultId();
        $statementDocument->legacyContextActivities();
        if ($statementDocument->isReferencing()) {
            // Copy values of referenced statement chain inside current statement for faster query-ing
            // (space-time tradeoff)
            $referencedStatementId = $statementDocument->getReferencedStatementId();
            $referencedStatement = $this->getStatementById($referencedStatementId);

            $existingReferences = [];
            if (null !== $referencedStatement->getReferences()) {
                $existingReferences = $referencedStatement->getReferences();
            }
            $existingReferences[] = $referencedStatement->getStatement();
            $statementDocument->setReferences($existingReferences);
        }
        $statements[] = $statementDocument->toArray();
        if ($statementDocument->isVoiding()) {
            $referencedStatementId = $statementDocument->getReferencedStatementId();
            $referencedStatement = $this->getStatementById($referencedStatementId);

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

        // $collection->insertMultiple($statements); // Batch operation is much faster ~600%
        // However, because we add every single statement to the access log, we can't use it
        // The only way to still use (fast) batch inserts would be to move the attachment of
        // statements to their respective log entries in a async queue!

        return $statementDocument;
    }

    public function postStatement($statementObject)
    {
        $statementDocument = $this->storeStatement($statementObject);
        $statementResult = new StatementResult();
        $statementResult->setStatementCursor([$statementObject]);
        $statementResult->setCurrentCount(1);
        $statementResult->setHasMore(false);
        return $statementResult;
    }

    public function postStatements($statementObjects)
    {
        $statementDocuments = [];
        foreach ($statementObjects as $statementObject) {
            $statementDocuments[] = $this->storeStatement($statementObject);
        }
        $statementResult = new StatementResult();
        $statementResult->setStatementCursor($statementDocuments);
        $statementResult->setCurrentCount(count($statementDocuments));
        $statementResult->setHasMore(false);
        return $statementResult;
    }

	public function putStatement($parameters, $statementObject)
    {
        // Check statementId exists
        if (!$parameters->has('statementId')) {
            throw new Exception('The statementId parameter is missing!', Resource::STATUS_BAD_REQUEST);
        }

        // Check statementId is acutally valid
        if(!Uuid::isValid($parameters->get('statementId'))){
            throw new Exception('The provided statement ID is invalid!', Resource::STATUS_BAD_REQUEST);
        }

        // Check statementId
        if (isset($statementObject['id'])) {
            // Check for match
            if ($statementObject['id'] !== $parameters->get('statementId')) {
                throw new \Exception('Statement ID query parameter doesn\'t match the given statement property', Resource::STATUS_BAD_REQUEST);
            }
        } else {
            $body['id'] = $parameters->get('statementId');
        }

        $statementDocument = $this->storeStatement($statementObject);
        $statementResult = new StatementResult();
        $statementResult->setStatementCursor([$statementObject]);
        $statementResult->setCurrentCount(1);
        $statementResult->setHasMore(false);
        return $statementResult;
    }

	public function deleteStatement($parameters)
    {
        throw \InvalidArgumentException('Statements cannot be deleted, only voided!', Resource::STATUS_INTERNAL_SERVER_ERROR);
    }

    /**
     * Gets the Access token to check for permissions.
     *
     * @return API\Document\Auth\AbstractToken
     */
    private function getAccessToken()
    {
        return $this->getSlim()->auth;
    }
}