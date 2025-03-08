<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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

namespace API\Util;

use League\Flysystem\Filesystem as FS;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

use API\Controller;
use API\Config;

// Maybe move this to API/Service and remove ODM dependency on Services. Check out the semantics of this...
class Filesystem
{
    /**
     * Build a path to a storage dir, based on 'publicRoot' (see Bootstrap:: initConfig())
     * @param array config
     *
     * @return string
     */
    public static function getStoragePath($config) {
        $root_dir = $config['local']['root_dir'];
        $public_root = Config::get('publicRoot', '');
        return $public_root.'/'.$root_dir;
    }

    /**
     * Generate al FilesystemAdapter (local aws)
     * @param array config
     *
     * @return League\Flysystem\Local\FilesystemAdapter
     */
    public static function generateAdapter($config)
    {
        // Customize how visibility is converted to unix permissions
        $visibility = PortableVisibilityConverter::fromArray([
            'file' => [
                'public' => 0640,
                'private' => 0604,
            ],
            'dir' => [
                'public' => 0740,
                'private' => 7664,
            ],
        ]);
        $typeInUse = $config['in_use'];

        if ($typeInUse  === 'local') {

            $root = Config::get('publicRoot');
            $filesystem = new FS(
                new LocalFilesystemAdapter(
                    self::getStoragePath($config),
                    $visibility,
                    \LOCK_EX
                )
            );

        } elseif ($typeInUse === 's3') {

            $client = S3Client::factory(array(
                'key' => $config['s3']['key'],
                'secret' => $config['s3']['secret'],
            ));
            $filesystem = new FS(new AwsS3Adapter($client, $config['s3']['bucket_name'], $config['s3']['prefix']));

        } else {
            throw new \Exception('Server error.', Controller::STATUS_INTERNAL_SERVER_ERROR);
        }

        return $filesystem;
    }

    public static function generateSHA2($rawData)
    {
        $hash = hash('sha256', $rawData);
        return $hash;
    }
}
