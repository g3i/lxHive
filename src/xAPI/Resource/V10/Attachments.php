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

namespace API\Resource\V10;

use API\Resource;
use API\Service\Attachment as AttachmentService;
use Slim\Helper\Set;

class Attachments extends Resource
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
        $this->setAttachmentService(new AttachmentService($this->getSlim()));
    }

    public function get()
    {
        $request = $this->getSlim()->request();

        // Check authentication
        $this->getSlim()->auth->checkPermission('attachments');

        $params = new Set($request->get());
        if (!$params->has('sha2')) {
            throw new \Exception('Missing sha2 parameter!', Resource::STATUS_BAD_REQUEST);
        }

        $sha2 = $params->get('sha2');

        $encoding = $params->get('encoding');
        // Fetch attachment metadata and data
        $metadata = $this->attachmentService->fetchMetadataBySha2($sha2);
        $data = $this->attachmentService->fetchFileBySha2($sha2);
        if ($encoding !== 'binary') {
            $data = base64_encode($data);
        }
        $this->getSlim()->response->headers->set('Content-Type', $metadata->getContentType());

        Resource::response(Resource::STATUS_OK, $data);
    }

    public function options()
    {
        //Handle options request
        $this->getSlim()->response->headers->set('Allow', 'GET');
        Resource::response(Resource::STATUS_OK);
    }

    /**
     * Gets the value of attachmentService.
     *
     * @return \API\Service\Attachment
     */
    public function getAttachmentService()
    {
        return $this->attachmentService;
    }

    /**
     * Sets the value of attachmentService.
     *
     * @param \API\Service\Attachment $attachmentService the attachment service
     *
     * @return self
     */
    public function setAttachmentService(\API\Service\Attachment $attachmentService)
    {
        $this->attachmentService = $attachmentService;

        return $this;
    }
}
