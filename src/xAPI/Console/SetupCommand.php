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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use API\Bootstrap;
use API\Admin\Setup;

class SetupCommand extends SymfonyCommand
{
    /**
     * Setup class.
     *
     * @var API\Admin\Setup $setup
     */
    private $setup;

    /**
     * @constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setup = new Setup();
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
        // 1. check config
        $bootstrapper = Bootstrap::factory(Bootstrap::None);
        if ($this->getSetup()->locateYaml('Config.yml')) {
            throw new \RuntimeException('A `Config.yml` file exists already. The LRS configuration would be overwritten. To restore the defaults you must manually remove the file first.');
        }

        $output->writeln('<info>Welcome to the setup of lxHive!</info>');

        // 2. LRS instance
        $helper = $this->getHelper('question');
        $question = new Question('Enter a name for this lxHive instance: ', 'Untitled');
        $name = $helper->ask($input, $output, $question);

        // 3. Mongo connection
        $connectionSuccess = false;
        while (!$connectionSuccess) {
            $question = new Question('Enter the URI of your MongoDB installation (default: "mongodb://127.0.0.1"): ', 'mongodb://127.0.0.1');
            $mongoHostname = $helper->ask($input, $output, $question);

            $connectionSuccess = $this->getSetup()->testDbConnection($mongoHostname);
            if (!$connectionSuccess) {
                $output->writeln('Connection unsuccessful, please try again.');
            }
        }

        // 4. Database
        $question = new Question('Enter the name of your MongoDB database (default: "lxHive"): ', 'lxHive');
        $mongoDatabase = $helper->ask($input, $output, $question);

        // 5. Create config
        $mergeConfig = ['name' => $name, 'storage' => ['in_use' => 'Mongo', 'Mongo' => ['host_uri' => $mongoHostname, 'db_name' => $mongoDatabase]]];
        $this->getSetup()->installYaml('Config.yml', $mergeConfig);

        if (!$this->getSetup()->locateYaml('Config.production.yml')) {
            $this->getSetup()->installYaml('Config.production.yml');
        }
        if (!$this->getSetup()->locateYaml('Config.development.yml')) {
            $this->getSetup()->installYaml('Config.development.yml');
        }
        $output->writeln('<info>*</info> Configuration saved!');

        // 7. DB Schema
        Bootstrap::reset();
        $this->getSetup()->installDb();
        $output->writeln('<info>*</info> Database set up!');

        // 8. oAuth scopes
        Bootstrap::reset();
        $this->getSetup()->initializeAuthScopes();
        $output->writeln('<info>*</info> OAuth scopes configured!');

        // 9. finish
        $output->writeln('<info>*</info> Configuration saved!');
        $output->writeln('<info>Setup complete!</info>');
    }

    /**
     * Gets the value of setup.
     *
     * @return mixed
     */
    public function getSetup()
    {
        return $this->setup;
    }
}
