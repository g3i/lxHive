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

namespace API\Document;

use Sokil\Mongo\Document;
use JsonSerializable;
use Rhumsaa\Uuid\Uuid;
use League\Url\Url;
use API\Resource;

class Statement extends Document implements JsonSerializable
{
    protected $_data = [
        'statement' => [
            'authority' => null,
            'id'        => null,
            'actor'     => null,
            'verb'      => null,
            'object'    => null,
            'timestamp' => null,
            'stored'    => null,
        ],
        'mongo_timestamp' => null,
        'voided'          => false,
        'logId'           => null
    ];

    public function setStatement($statement)
    {
        $this->_data['statement'] = $statement;
    }

    public function getStatement()
    {
        return $this->_data['statement'];
    }

    public function setStored($timestamp)
    {
        $this->_data['statement']['stored'] = $timestamp;
    }

    public function getStored()
    {
        return $this->_data['statement']['stored'];
    }

    public function setTimestamp($timestamp)
    {
        $this->_data['statement']['timestamp'] = $timestamp;
    }

    public function getTimestamp()
    {
        return $this->_data['statement']['timestamp'];
    }

    public function setMongoTimestamp($timestamp)
    {
        $this->_data['mongo_timestamp'] = $timestamp;
    }

    public function getMongoTimestamp()
    {
        return $this->_data['mongo_timestamp'];
    }

    public function setDefaultTimestamp()
    {
        if (!isset($this->_data['statement']['timestamp']) || null === $this->_data['statement']['timestamp']) {
            $this->_data['statement']['timestamp'] = $this->_data['statement']['stored'];
        }
    }

    public function isVoiding()
    {
        if (isset($this->_data['statement']['verb']['id'])
            && ($this->_data['statement']['verb']['id'] === 'http://adlnet.gov/expapi/verbs/voided')
            && isset($this->_data['statement']['object']['objectType'])
            && ($this->_data['statement']['object']['objectType'] === 'StatementRef')
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function isReferencing()
    {
        if (isset($this->_data['statement']['object']['objectType'])
            && ($this->_data['statement']['object']['objectType'] === 'StatementRef'))
        {
            return true;
        } else {
            return false;
        }
    }

    public function getReferencedStatement()
    {
        $referencedId = $this->_data['statement']['object']['id'];

        $referencedStatement = $this->getCollection()->find()->where('statement.id', $referencedId)->current();

        if (null === $referencedStatement) {
            throw new \InvalidArgumentException('Referenced statement does not exist!', Resource::STATUS_BAD_REQUEST);
        }

        return $referencedStatement;
    }

    public function fixAttachmentLinks($baseUrl)
    {
        if (isset($this->_data['statement']['attachments'])) {
            foreach ($this->_data['statement']['attachments'] as &$attachment) {
                if (!isset($attachment['fileUrl'])) {
                    $url = Url::createFromUrl($baseUrl);
                    $url->getQuery()->modify(['sha2' => $attachment['sha2']]);
                    $attachment['fileUrl'] =  $url->__toString();
                }
            }
        }
    }

    public function convertExtensionKeysToUnicode()
    {
        if (isset($this->_data['statement']['context']['extensions'])) {
            foreach ($this->_data['statement']['context']['extensions'] as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('.', '\uFF0E', $extensionKey);
                $this->_data['statement']['context']['extensions'][$newExtensionKey] = $extensionValue;
                unset($this->_data['statement']['context']['extensions'][$extensionKey]);
            }
        }

        if (isset($this->_data['statement']['result']['extensions'])) {
            foreach ($this->_data['statement']['result']['extensions'] as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('.', '\uFF0E', $extensionKey);
                $this->_data['statement']['result']['extensions'][$newExtensionKey] = $extensionValue;
                unset($this->_data['statement']['result']['extensions'][$extensionKey]);
            }
        }

        if (isset($this->_data['statement']['object']['definition']['extensions'])) {
            foreach ($this->_data['statement']['object']['definition']['extensions'] as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('.', '\uFF0E', $extensionKey);
                $this->_data['statement']['object']['definition']['extensions'][$newExtensionKey] = $extensionValue;
                unset($this->_data['statement']['object']['definition']['extensions'][$extensionKey]);
            }
        }
    }

    public function convertExtensionKeysFromUnicode()
    {
        if (isset($this->_data['statement']['context']['extensions'])) {
            foreach ($this->_data['statement']['context']['extensions'] as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('\uFF0E', '.', $extensionKey);
                $this->_data['statement']['context']['extensions'][$newExtensionKey] = $extensionValue;
                unset($this->_data['statement']['context']['extensions'][$extensionKey]);
            }
        }

        if (isset($this->_data['statement']['result']['extensions'])) {
            foreach ($this->_data['statement']['result']['extensions'] as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('\uFF0E', '.', $extensionKey);
                $this->_data['statement']['result']['extensions'][$newExtensionKey] = $extensionValue;
                unset($this->_data['statement']['result']['extensions'][$extensionKey]);
            }
        }

        if (isset($this->_data['statement']['object']['definition']['extensions'])) {
            foreach ($this->_data['statement']['object']['definition']['extensions'] as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('\uFF0E', '.', $extensionKey);
                $this->_data['statement']['object']['definition']['extensions'][$newExtensionKey] = $extensionValue;
                unset($this->_data['statement']['object']['definition']['extensions'][$extensionKey]);
            }
        }
    }

    public function extractActivities()
    {
        $activities = [];
        // Main activity
        if ((isset($this->_data['statement']['object']['objectType']) && $this->_data['statement']['object']['objectType'] === 'Activity') || !isset($this->_data['statement']['object']['objectType'])) {
            $activity = $this->_data['statement']['object'];
            
            // Sort of a hack - PHP's copy-on-write needs to be executed, otherwise the MongoDB PHP driver
            // overwrites the contents of the variable being passed to the batchInsert call - regardless of
            // whether the variable has been passed by reference or not!
            // See more:
            // http://php.net/manual/en/mongocollection.insert.php Insert behaviour
            // http://php.net/manual/en/mongocollection.batchinsert.php Should behave the same as insert, however, does not
            // https://jira.mongodb.org/browse/PHP-383
            // http://www.phpinternalsbook.com/zvals/memory_management.html#reference-counting-and-copy-on-write
            // 
            // TODO: Report bug to Mongo bug tracker
            // 
            $activity['DUMMY'] = 'DUMMY';
            unset($activity['DUMMY']);
            
            $activities[] = $activity;
        }

        /* Commented out for now due to performance reasons
        // Context activities
        if (isset($this->_data['statement']['context']['contextActivities'])) {
            if (isset($this->_data['statement']['context']['contextActivities']['parent'])) {
                foreach ($this->_data['statement']['context']['contextActivities']['parent'] as $singleActivity) {
                    $activities[] = $singleActivity;
                }
            }
            if (isset($this->_data['statement']['context']['contextActivities']['category'])) {
                foreach ($this->_data['statement']['context']['contextActivities']['category'] as $singleActivity) {
                    $activities[] = $singleActivity;
                }
            }
            if (isset($this->_data['statement']['context']['contextActivities']['grouping'])) {
                foreach ($this->_data['statement']['context']['contextActivities']['grouping'] as $singleActivity) {
                    $activities[] = $singleActivity;
                }
            }
            if (isset($this->_data['statement']['context']['contextActivities']['other'])) {
                foreach ($this->_data['statement']['context']['contextActivities']['other'] as $singleActivity) {
                    $activities[] = $singleActivity;
                }
            }
        }
        // SubStatement activity check
        if (isset($this->_data['statement']['object']['objectType']) && $this->_data['statement']['object']['objectType'] === 'SubStatement') {
            if ((isset($this->_data['statement']['object']['object']['objectType']) && $this->_data['statement']['object']['object']['objectType'] === 'Activity') || !isset($this->_data['statement']['object']['object']['objectType']) {
                $activities[] = $this->_data['statement']['object']['object'];
            }
        }*/

        return $activities;
    }

    public function jsonSerialize()
    {
        return $this->getStatement();
    }

    public function setDefaultId()
    {
        // If no ID has been set, set it
        if (empty($this->_data['statement']['id']) || $this->_data['statement']['id'] === null) {
            $this->_data['statement'] = ['id' => Uuid::uuid4()->toString()] + $this->_data['statement'];
        }
    }

    public function renderExact()
    {
        $this->convertExtensionKeysFromUnicode();
        return $this->getStatement();
    }

    public function renderMeta()
    {
        return $this->getStatement()['id'];
    }

    public function renderCanonical()
    {
        throw new \InvalidArgumentException('The \'canonical\' statement format is currently not supported.', Resource::STATUS_NOT_IMPLEMENTED);
    }

    public function renderIds()
    {
        $this->convertExtensionKeysFromUnicode();
        $statement = $this->getStatement();

        if ($statement['actor']['objectType'] === 'Group') {
            $statement['actor'] = array_map(function ($singleMember) {
                return $this->simplifyObject($singleMember);
            }, $statement['actor']);
        } else {
            $statement['actor'] = $this->simplifyObject($statement['actor']);
        }

        if ($statement['object']['objectType'] !== 'SubStatement') {
            $statement['object'] = $this->simplifyObject($statement['object']);
        } else {
            if ($statement['object']['actor']['objectType'] === 'Group') {
                $statement['object']['actor'] = array_map(function ($singleMember) {
                    return $this->simplifyObject($singleMember);
                }, $statement['object']['actor']);
            } else {
                $statement['object']['actor'] = $this->simplifyObject($statement['object']['actor']);
            }
            $statement['object']['object'] = $this->simplifyObject($statement['object']['object']);
        }

        return $statement;
    }

    private function simplifyObject($object)
    {
        if (isset($object['mbox'])) {
            $uniqueIdentifier = 'mbox';
        } elseif (isset($object['mbox_sha1sum'])) {
            $uniqueIdentifier = 'mbox_sha1sum';
        } elseif (isset($object['openid'])) {
            $uniqueIdentifier = 'openid';
        } elseif (isset($object['account'])) {
            $uniqueIdentifier = 'account';
        } elseif (isset($object['id'])) {
            $uniqueIdentifier = 'id';
        }

        $object = [
            'objectType' => $object['objectType'],
            $uniqueIdentifier => $object[$uniqueIdentifier]
        ];

        return $object;
    }
}
