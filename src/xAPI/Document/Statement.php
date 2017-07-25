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
 *
 * Projected Usage
 *
 *  POST/PUT:
 *  $document = new \API\Document\Statement($parsedJson, 'UNTRUSTED', '1.0.3');
 *  $statement = $document->validate()->normalize()->document(); // validated and normalized stdClass, ready for storage, changes the state with each chain ['UNTRUSTED->VALIDTED->READY]
 *
 *  REST response
 *  $document = new \API\Document\Statement($mongoDocument, 'TRUSTED', '1.0.3');
 *  $document->validate()->normalize(); //deals with minor incositencies, will in future also remove meta properties
 *  $json = json_encode($document);
 *
 *  $document will have convenience methods and reveal the convenience methods of subproperties
 *  $document->isReferencing();
 *  $document->actor->isAgent();
 *  $document->object->isSubStatement();
 *
 *  etc..
 */

namespace API\Document;

use Ramsey\Uuid\Uuid;
use League\Url\Url;
use API\Controller;
use API\Document;
use API\DocumentState;

// TODO 0.9.6

class Statement extends Document
{
    public static function fromDatabase($document)
    {
        $documentState = DocumentState::TRUSTED;
        $version = $document->version;
        $statement = new self($document, $documentState, $version);
        return $statement;
    }

    public static function fromApi($document, $version)
    {
        $documentState = DocumentState::UNTRUSTED;
        $data = (object)[];
        $data->statement = $document;
        $statement = new self($data, $documentState, $version);
        return $statement;
    }

    public function validate()
    {
        // required check props, additional props: basic actor, object, result
        /*$validator = new Validator\Statement($this->document->actor, $this->state, $this->version);
        $validator->validate($this->document, $this->mode); //throws Exceptions

        $this->actor = new Actor($this->document->actor, $this->state, $this->version);
        $this->document->actor = $this->actor->document();

        $this->verb = new Verb($this->document->result, $this->state, $this->version);
        $this->document->verb = $this->verb->document();

        $this->object = new Object_($this->document->object, $this->state, $this->version);
        $this->document->object = $this->object->document();

        // optional props
        if(isset($this->document->result)){
            $this->result = new Result($this->document->result, $this->state, $this->version);
            $this->document->result = $this->result->document();
        }

        return $this;*/
    }

    public function normalize()
    {
        // Actually there is something to do here - add metadata!
        // For example, adding the mongo_timestamp
        // Maybe also adding the version in a version key!

        // nothing to do here sub modules take care
        // @Joerg: What are submodules?
        return $this;
    }

    /*public function get($key)
    {
        if (isset($this->data['statement'][$key])) {
            return $this->data['statement'][$key];
        }
    }

    public function set($key, $value)
    {
        $this->data['statement'][$key] = $value;
    }

    public function getStatement()
    {
        if (isset($this->data['statement->)) {
            return $this->data['statement->;
        }
    }

    public function getMetadata()
    {
        if (isset($this->data['metadata->)) {
            return $this->data['metadata->;
        }
    }*/

    public function getId()
    {
        return $this->data->{'_id'};
    }

    public function setStored($timestamp)
    {
        $this->data->statement->stored = $timestamp;
    }

    public function getStored()
    {
        return $this->data->statement->stored;
    }

    public function setTimestamp($timestamp)
    {
        $this->data->statement->timestamp = $timestamp;
    }

    public function getTimestamp()
    {
        return $this->data->statement->timestamp;
    }

    public function setMongoTimestamp($timestamp)
    {
        $this->data->mongo_timestamp = $timestamp;
    }

    public function getMongoTimestamp()
    {
        return $this->data->mongo_timestamp;
    }

    public function renderExact()
    {
        $this->convertExtensionKeysFromUnicode();

        return $this->data->statement;
    }

    public function renderMeta()
    {
        return $this->data->statement->id;
    }

    public function renderCanonical()
    {
        throw new \InvalidArgumentException('The \'canonical\' statement format is currently not supported.', Controller::STATUS_NOT_IMPLEMENTED);
    }

    public function setDefaultTimestamp()
    {
        if (!isset($this->data->statement->timestamp) || null ===  $this->data->statement->timestamp) {
             $this->data->statement->timestamp =  $this->data->statement->stored;
        }
    }

    /**
     * Mutate legacy statement.context.contextActivities
     * wraps single activity object (per type) into an array.
     */
    public function legacyContextActivities()
    {
        if (!isset($this->data->statement->context)) {
            return;
        }
        if (!isset($this->data->statement->context->contextActivities)) {
            return;
        }
        foreach ($this->data->statement->context->contextActivities as $type => $value) {
            // We are a bit rat-trapped because statement is an associative array, most efficient way to check if numeric array is here to check for required 'id' property
            if (isset($value->id)) {
                $this->data->statement->context->contextActivities->{$type} = [$value];
            }
        }
    }

    public function isVoiding()
    {
        if (isset($this->data->statement->verb->id)
            && ($this->data->statement->verb->id === 'http://adlnet.gov/expapi/verbs/voided')
            && isset($this->data->statement->object->objectType)
            && ($this->data->statement->object->objectType === 'StatementRef')
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function isReferencing()
    {
        if (isset($this->data->statement->object->objectType)
            && ($this->data->statement->object->objectType === 'StatementRef')) {
            return true;
        } else {
            return false;
        }
    }

    public function getReferencedStatementId()
    {
        $referencedId = $this->data->statement->object->id;

        return $referencedId;
    }

    public function fixAttachmentLinks($baseUrl)
    {
        if (isset($this->data->statement->attachments)) {
            if (!is_array($this->data->statement->attachments)) {
                return;
            }
            foreach ($this->data->statement->attachments as &$attachment) {
                if (!isset($attachment->fileUrl)) {
                    $url = Url::createFromUrl($baseUrl);
                    $url->getQuery()->modify(['sha2' => $attachment->sha2]);
                    $attachment->fileUrl = $url->__toString();
                }
            }
        }
    }

    public function convertExtensionKeysToUnicode()
    {
        if (isset($this->data->statement->context->extensions)) {
            if (!is_array($this->data->statement->context->extensions)) {
                return;
            }
            foreach ($this->data->statement->context->extensions as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('.', '\uFF0E', $extensionKey);
                $this->data->statement->context->extensions->{$newExtensionKey} = $extensionValue;
                unset($this->data->statement->context->extensions->{$extensionKey});
            }
        }

        if (isset($this->data->statement->result->extensions)) {
            if (!is_array($this->data->statement->result->extensions)) {
                return;
            }
            foreach ($this->data->statement->result->extensions as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('.', '\uFF0E', $extensionKey);
                $this->data->statement->result->extensions->{$newExtensionKey} = $extensionValue;
                unset($this->data->statement->result->extensions->{$extensionKey});
            }
        }

        if (isset($this->data->statement->object->definition->extensions)) {
            if (!is_array($this->data->statement->object->definition->extensions)) {
                return;
            }
            foreach ($this->data->statement->object->definition->extensions as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('.', '\uFF0E', $extensionKey);
                $this->data->statement->object->definition->extensions->{$newExtensionKey} = $extensionValue;
                unset($this->data->statement->object->definition->extensions->{$extensionKey});
            }
        }
    }
    public function setDefaultId()
    {
        // If no ID has been set, set it
        if (empty($this->data->statement->id) || $this->data->statement->id === null) {
            $this->data->statement->id = Uuid::uuid4()->toString();
        }
    }

    public function convertExtensionKeysFromUnicode()
    {
        if (isset($this->data->statement->context->extensions)) {
            if (!is_array($this->data->statement->context->extensions)) {
                return;
            }
            foreach ($this->data->statement->context->extensions as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('\uFF0E', '.', $extensionKey);
                $this->data->statement->context->extensions->{$newExtensionKey} = $extensionValue;
                unset($this->data->statement->context->extensions->{$extensionKey});
            }
        }

        if (isset($this->data->statement->result->extensions)) {
            if (!is_array($this->data->statement->result->extensions)) {
                return;
            }
            foreach ($this->data->statement->result->extensions as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('\uFF0E', '.', $extensionKey);
                $this->data->statement->result->extensions->{$newExtensionKey} = $extensionValue;
                unset($this->data->statement->result->extensions->{$extensionKey});
            }
        }

        if (isset($this->data->statement->object->definition->extensions)) {
            if (!is_array($this->data->statement->object->definition->extensions)) {
                return;
            }
            foreach ($this->data->statement->object->definition->extensions as $extensionKey => $extensionValue) {
                $newExtensionKey = str_replace('\uFF0E', '.', $extensionKey);
                $this->data->statement->object->definition->extensions->{$newExtensionKey} = $extensionValue;
                unset($this->data->statement->object->definition->extensions->{$extensionKey});
            }
        }
    }

    public function renderIds()
    {
        $this->convertExtensionKeysFromUnicode();
        $statement = $this->data->statement;

        if ($statement->actor->objectType === 'Group') {
            $statement->actor = array_map(function ($singleMember) {
                return $this->simplifyObject($singleMember);
            }, $statement->actor);
        } else {
            $statement->actor = $this->simplifyObject($statement->actor);
        }

        if ($statement->object->objectType !== 'SubStatement') {
            $statement->object = $this->simplifyObject($statement->object);
        } else {
            if ($statement->object->actor->objectType === 'Group') {
                $statement->object->actor = array_map(function ($singleMember) {
                    return $this->simplifyObject($singleMember);
                }, $statement->object->actor);
            } else {
                $statement->object->actor = $this->simplifyObject($statement->object->actor);
            }
            $statement->object->object = $this->simplifyObject($statement->object->object);
        }

        return $statement;
    }
}
