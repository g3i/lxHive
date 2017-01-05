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

use API\Validator;

class Statement extends Document implements DocumentInterface
{
    public static function fromDatabase($document)
    {
        $documentState = DocumentState::TRUSTED;
        $version = $document['version'];
        $statement = new self($document, $documentState, $version);
        return $statement;
    }

    public static function fromApi($document, $version)
    {
        $documentState = DocumentState::UNTRUSTED;
        $data = ['statement' => $document];
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

    public function get($key)
    {
        if (isset($this->data['statement'][$key])) {
            return $this->data['statement'][$key];
        }
    }

    public function set($key, $value)
    {
        $this->data['statement'][$key] = $value;
    }

    public function getMetadata()
    {
        if (isset($this->data['metadata'])) {
            return $this->data['metadata'];
        }
    }

    public function jsonSerialize()
    {
        return $this->document;
    }


}