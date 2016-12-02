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
use Rhumsaa\Uuid\Uuid;

class Statement extends Service
{
    /**
     * Fetches statements according to the given parameters.
     *
     * @param array $request The HTTP request object.
     *
     * @return array An array of statement objects.
     */
    public function statementGet($request)
    {
        $parameters = new Set($request->get());

        $statementResult = $this->getStorageAdapter()->getStatementsFiltered($parameters);

        return $statementResult;
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

        // TODO: Separate this into some sort of parser for multipart!
        // TODO2: Add the attachment service to the adapters!
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

        // Multiple statements
        if ($this->areMultipleStatements($body)) {
            $statementResult = $this->getStorageAdapter()->postStatements($body);
        } else {
            // Single statement
            $statementResult = $this->getStorageAdapter()->postStatement($body);
        }

        return $statementResult;
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

        // TODO: Separate this into some sort of parser for multipart!
        // TODO2: Add the attachment service to the adapters!
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

        // Single
        $parameters = new Set($request->get());

        $statementResult = $this->getStorageAdapter()->putStatement($parameters, $body);

        return $statementResult;
    }

    // Quickest solution for checking 1D vs 2D assoc arrays
    private function areMultipleStatements(&$array)
    {
        return ($array === array_values($array));
    }
}
