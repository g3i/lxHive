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

namespace API\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use API\Config;
use API\Bootstrap;
use API\Admin\Setup;
use API\Admin\Validator;

use RunTimeException;
use API\AppInitException;
use API\Admin\AdminException;

class SetupCommand extends SymfonyCommand
{
    /**
     * @var Setup $setup
     */
    private $setup;

    /**
     * @var Validator $validator
     */
    private $validator;

    /**
     * @var array $sequence
     */
    private $sequence = [
        'io_checkConfig'            => 'Install default configuration',
        'io_setLrsInstance'         => 'Configure Lrs instance',
        'io_setMongoStorage'        => 'Configure Mongo database',

        'reboot'                    => 'Reboot app with new configuration',

        'io_verifyDatabaseVersion'   => 'Verify Database compatibility',
        'io_installDatabaseSchema'  => 'Install lxHive database schemas',
        'io_installAuthScopes'      => 'Setup oAuth scopes',
        'io_setLocalFileStorage'    => 'Setup local file storage'
    ];

    /**
     * @constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setup = new Setup();
        $this->validator = new Validator();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Sets up lxHive')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('<info>Welcome to the setup of lxHive!</info>');
        $io->newLine();

        // check config
        Bootstrap::factory(Bootstrap::None);
        if ($this->setup->locateYaml('Config.yml')) {
            throw new RuntimeException('A `Config.yml` file exists already. The LRS configuration would be overwritten. To restore the defaults you must manually remove the file first.');
        }

        $count = 0;
        foreach ($this->sequence as $callback => $title) {
            $count++;
            $io->section('<info>['.$count.'/'.count($this->sequence).']</info> '.$title);
            call_user_func_array([$this, $callback], [$io]);
        }

        // finish
        $io->success('Setup complete!');
        $io->text('<info> --> </info> NEXT: Create your first user with <comment>./X user:create</comment>');
        $io->newLine();
    }

    /**
     * Installs default configruation files from templates
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws AppInitException
     */
    private function reboot($io)
    {
        //TODO
        Bootstrap::reset();
        Bootstrap::factory(Bootstrap::Console);
        $io->listing(['Re-booted app']);
    }

    /**
     * Installs default configruation files from templates
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws AdminException
     */
    private function io_checkConfig($io)
    {
        $msg = [];

        $this->setup->installYaml('Config.yml');
        $msg[] = 'Config.yml installed';

        $this->setup->removeYaml('Config.production.yml');
        $this->setup->installYaml('Config.production.yml');
        $msg[] = 'Config.production.yml installed';

        $this->setup->removeYaml('Config.development.yml');
        $this->setup->installYaml('Config.development.yml');
        $msg[] = 'Config.development.yml installed';

        $io->listing($msg);
    }

    /**
     * Installs default configruation files from templates
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws AdminException
     */
    private function io_setLrsInstance($io)
    {
        $name = $io->ask('Enter a name for this lxHive instance: ', 'lxHive', function ($answer) {
            $this->validator->validateName($answer);
            return $answer;
        });

        $this->setup->updateYaml('Config.yml', [
            'name' => $name
        ]);
        $io->listing(['lxHive instance: '. $name]);
    }

    /**
     * Set Mongo Databas connection
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws AdminException
     */
    private function io_setMongoStorage($io)
    {
        $msg = [];

        $host = $io->ask('Enter the URI of your MongoDB installation:', 'mongodb://127.0.0.1', function ($answer) use ($io) {
            $conn = $this->setup->testDbConnection($answer);
            if (!$conn) {
                throw new RuntimeException('Connection unsuccessful, please try again.');
            }
            return $answer;
        });
        $msg[] = 'Mongo connection: '. $host;

        $db = $io->ask('Enter the name of your MongoDB database:', 'lxHive', function ($answer) {
            $this->validator->validateMongoName($answer);
            return $answer;
        });
        $msg[] = 'Mongo database: '. $db;

        $this->setup->updateYaml('Config.yml', [
            'storage' => [
                'in_use' => 'Mongo',
                'Mongo' => [
                    'host_uri' => $host,
                    'db_name'=> $db,
                ]
            ]
        ]);
        $io->listing($msg);
    }

    /**
     * Check Database compatibility
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws RuntimeException
     */
    private function io_verifyDatabaseVersion($io)
    {
        $msg = $this->setup->verifyDbVersion(); // throws exception on fail
        $io->listing(['DB is compatible: ' . $msg]);
    }

    /**
     * Installs Database schemas
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws RuntimeException
     */
    private function io_installDatabaseSchema($io)
    {
        $this->setup->installDb(); // throws exception on fail
        $io->listing(['DB schema installed']);
    }

    /**
     * Installs AuthScopes
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws RuntimeException
     */
    private function io_installAuthScopes($io)
    {
        // logic migrated to session service, left for notification
        $io->listing(['AuthScopes installed']);
    }

    /**
     * Installs local files torage
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     *
     * @return void
     * @throws RuntimeException
     */
    private function io_setLocalFileStorage($io)
    {
        $msg = [];

        try {
            $info = $this->setup->installFileStorage();
            $owner = posix_getpwuid($info->getOwner());
            $group = posix_getgrgid($info->getGroup());

            $msg[] = '' .$info->getPath();
            $msg[] = 'permission: ' .substr(sprintf('%o', $info->getPerms()), -4);
            $msg[] = 'path: '.$info->getRealPath();
            $msg[] = 'owner: '.$owner['name'];
            $msg[] = 'group: '.$group['name'];
        } catch (AdminException $e) {
            $io->warning('Unable to create Local file Storage. Please create manually.');
            $io->text('<error>Error message:</error> '.$e->getMessage());
        }

        $io->listing($msg);
        $io->note('Please make sure your webserver has read/write access to the "storage" directories.');
    }
}
