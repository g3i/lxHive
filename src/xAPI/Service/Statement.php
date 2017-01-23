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
 */

namespace API\Service;

use API\Service;
use API\Resource;
use API\HttpException as Exception;

class Statement extends Service
{
    /**
     * Fetches statements according to the given parameters.
     *
     * @param array $request The HTTP request object.
     *
     * @return array An array of statement objects.
     */
    public function statementGet()
    {
        $parameters = $this->getContainer()['parser']->getData()->getParameters();

        $statementResult = $this->getStorage()->getStatementStorage()->get($parameters);

        return $statementResult;
    }

    /**
     * Tries to  a statement with a specified statementId.
     *
     * @return array An array of statement documents or a single statement document.
     */
    public function statementPost()
    {
        $this->validateJsonMediaType($this->getContainer()['parser']->getData());

        if (count($this->getContainer()['parser']->getAttachments()) > 0) {
            $fsAdapter = \API\Util\Filesystem::generateAdapter($this->getContainer()['settings']['filesystem']);

            foreach ($this->getContainer()['parser']->getAttachments() as $attachment) {
                $attachmentBody = $attachment->getPayload();

                $detectedEncoding = mb_detect_encoding($attachmentBody);
                $contentEncoding = isset($attachment->getHeaders()['content-transfer-encoding']) ? $attachment->getHeaders()['content-transfer-encoding'] : null;

                if ($detectedEncoding === 'UTF-8' && ($contentEncoding === null || $contentEncoding === 'binary')) {
                    try {
                        $attachmentBody = iconv('UTF-8', 'ISO-8859-1//IGNORE', $attachmentBody);
                    } catch (\Exception $e) {
                        //Use raw file on failed conversion (do nothing!)
                    }
                }

                $hash = $attachment->getHeaders()['x-experience-api-hash'][0];
                $contentType = $attachment->getHeaders()['content-type'][0];

                $this->getStorage()->getAttachmentStorage()->storeAttachment($hash, $contentType);

                $fsAdapter->put($hash, $attachmentBody);
            }
        }

        $body = $this->getContainer()['parser']->getData()->getPayload();

        // Multiple statements
        if ($this->areMultipleStatements($body)) {
            $statementResult = $this->getStorage()->getStatementStorage()->insertMultiple($body);
        } else {
            // Single statement
            $statementResult = $this->getStorage()->getStatementStorage()->insertOne($body);
        }

        return $statementResult;
    }

    /**
     * Tries to PUT a statement with a specified statementId.
     *
     * @return
     */
    public function statementPut()
    {
        $this->validateJsonMediaType($this->getContainer()['parser']->getData());

        if (count($this->getContainer()['parser']->getAttachments()) > 0) {
            $fsAdapter = \API\Util\Filesystem::generateAdapter($this->getContainer()['settings']['filesystem']);

            foreach ($this->getContainer()['parser']->getAttachments() as $attachment) {
                $attachmentBody = $attachment->getPayload();

                $detectedEncoding = mb_detect_encoding($attachmentBody);
                $contentEncoding = $attachment->getHeaders()['Content-Transfer-Encoding'];

                if ($detectedEncoding === 'UTF-8' && ($contentEncoding === null || $contentEncoding === 'binary')) {
                    try {
                        $attachmentBody = iconv('UTF-8', 'ISO-8859-1//IGNORE', $attachmentBody);
                    } catch (\Exception $e) {
                        // Use raw file on failed conversion (do nothing!)
                    }
                }

                $hash = $attachment->getHeaders()['X-Experience-API-Hash'];
                $contentType = $part->getHeaders()['Content-Type'];

                $this->getStorage()->getAttachmentStorage()->storeAttachment($hash, $contentType);

                $fsAdapter->put($hash, $attachmentBody);
            }
        }

        // Single
        $parameters = $this->getContainer()['parser']->getData()->getParameters();
        $body = $this->getContainer()['parser']->getData()->getPayload();

        $statementResult = $this->getStorage()->getStatementStorage()->put($parameters, $body);

        return $statementResult;
    }

    // Quickest solution for validateing 1D vs 2D assoc arrays
    private function areMultipleStatements(&$array)
    {
        return $array === array_values($array);
    }

    private function validateJsonMediaType($jsonRequest)
    {
        // TODO: Move header validation in json-schema as well
        if (strpos($jsonRequest->getHeaders()['content-type'][0], 'application/json') !== 0) {
            throw new Exception('Media type specified in Content-Type header must be \'application/json\'!', Resource::STATUS_BAD_REQUEST);
        }
    }
}
