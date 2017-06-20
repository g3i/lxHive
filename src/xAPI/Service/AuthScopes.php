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
use API\Resource;

class AuthScopes extends Service
{

    private $matrix = [
        'super' => [
            'all'
        ],
        'all' => [
            'statements/write',
            'attachments',
            'state',
            'profile',
            'define',
            'all/read',
        ],
        'all/read' => [
            'statements/read',
            'attachments',
            'state', //TODO limit to read (service)
            'profile', //TODO limit to read (service)
        ],
        'statements/write' => [
            'statements/read/mine'
        ],
        'statements/read' => [
            'statements/read/mine'
        ]
    ];

    /**
     * return permission matrix
     * @return int
     */
    public function getMatrix()
    {
        return $this->matrix;
    }

    /**
     * return permission matrix
     * @return int
     */
    public function getChildrenFor($name)
    {
        if(!isset($this->matrix[$name])) {
            return [];
        }

        $children = $this->matrix[$name];
        foreach($children as $c){
            if (in_array($name, $children)) {
                continue;
            }
            $children = array_merge($children, $this->getChildrenFor($c));
        }

        return array_unique($children);
    }


    /**
     * counts permission doocuments
     * @return int
     */
    public function count()
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');
        $cursor     = $collection->find();
        return $cursor->count();
    }

    /**
     * Gets all permission documents
     * @param bool $dictionary return as associative array of documents (permission name)
     * @return array of \Sokil\Mongo\Document
     */
    public function fetchAll($dictionary = false)
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');
        $cursor     = $collection->find();
        $documents  = $cursor->findAll();

        if (!$dictionary) {
            return $documents;
        }

        $dictionary = [];
        foreach ($cursor as $permission) {
            $dictionary[$permission->getName()] = $permission;
        }
        return $dictionary;
    }

    /**
     * Gets a single registered permission document by id
     * @param string $id
     * @return \Sokil\Mongo\Document|null
     */
    public function findById($id)
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');
        return $collection->getDocument($id);
    }

    /**
     * Gets a single registered permission document by name
     * @param string $name
     * @return \Sokil\Mongo\Document|null
     */
    public function findByName($name)
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');
        $cursor     = $collection->find();
        $cursor->where('name', $name);
        $scopeDocument = $cursor->current();
        return $scopeDocument;
    }

    /**
     * Gets a list permissions document by names
     * Note: this function does NOT validate if a given permission name exists.
     *
     * @param array $names
     * @return array of \Sokil\Mongo\Document
     */
    public function findByNames($names, $dictionary = false)
    {
        $collection = $this->getDocumentManager()->getCollection('authScopes');
        $cursor     = $collection->find();


        $cursor->where('name', ['$in' => $names]);
        $documents = $cursor->findAll();

        // Force dictionary mode for legacy LRS:
        //   Deals with existing LRS suffering from #103: "./X setup:oauth - duplication of scopes when called multiple times"
        //   TODO for future release: remove, collection indexing (unique), add migration script

        $dictionary = [];
        foreach ($cursor as $permission) {
            $dictionary[$permission->getName()] = $permission;
        }

        return ($dictionary) ? $dictionary : array_values($dictionary);
    }

}
