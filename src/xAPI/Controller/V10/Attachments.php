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

namespace API\Controller\V10;

use API\Controller;
use API\Service\Attachment as AttachmentService;
use API\Util;

class Attachments extends Controller
{
    /**
     * @var \API\Service\Attachment
     */
    private $attachmentService;

    /**
     * Get statement service.
     */
    public function init()
    {
        $this->attachmentService = new AttachmentService($this->getContainer());
    }

    public function get()
    {
        $request = $this->getContainer()->get('parser')->getData();

        // Check authentication
        $this->getContainer()->get('auth')->requirePermission('attachments');

        $params = new Util\Collection($request->getParameters());
        if (!$params->has('sha2')) {
            throw new \Exception('Missing sha2 parameter!', Controller::STATUS_BAD_REQUEST);
        }

        $sha2 = $params->get('sha2');
        $encoding = $params->get('encoding');

        // Fetch attachment metadata and data
        $metadata = $this->attachmentService->fetchMetadataBySha2($sha2);
        $data = $this->attachmentService->fetchFileBySha2($sha2);
        if ($encoding !== 'binary') {
            $data = base64_encode($data);
        }

        $metadataDocument = new \API\Document\Generic($metadata);
        $this->setResponse($this->getResponse()->withHeader('Content-Type', $metadataDocument->getContentType()));

        return $this->response(Controller::STATUS_OK, $data);
    }

    public function options()
    {
        //Handle options request
        $this->setResponse($this->getResponse()->withHeader('Allow', 'GET'));
        return $this->response(Controller::STATUS_OK);
    }
}
