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

namespace API\Console;

use API\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use Sokil\Mongo\Client;

class SetupDbCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('setup:db')
            ->setDescription('Sets up the MongoDB database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Welcome to the setup of lxHive!</info>');

        // instance name
        $helper = $this->getHelper('question');
        $question = new Question('Enter a name for this lxHive instance: ', 'Untitled');
        $name = $helper->ask($input, $output, $question);
        $output->writeln('<info>x</info> Instance name set to "' .$name.'"!');

        // Mongo connection
        $connectionSuccess = false;
        while (!$connectionSuccess) {
            $question = new Question('Enter the URI of your MongoDB installation (default: "mongodb://127.0.0.1"): ', 'mongodb://127.0.0.1');
            $mongoHostname = $helper->ask($input, $output, $question);

            $client = new Client($mongoHostname);
            try {
                $mongoVersion = $client->getDbVersion();
                $output->writeln('<info>x</info> Connection successful, MongoDB version '.$mongoVersion.'.');
                $connectionSuccess = true;
            } catch (\MongoConnectionException $e) {
                $output->writeln('<error>!</error> Connection unsuccessful, please try again.');
            }
        }

        // Mongo database
        $question = new Question('Enter the name of your MongoDB database (default: "lxHive"): ', 'lxHive');
        $mongoDatabase = $helper->ask($input, $output, $question);
        $output->writeln('<info>x</info> DB setup complete!');

        // Merge and store config.yml
        $currentConfig = Yaml::parse(file_get_contents(__DIR__.'/../Config/Config.template.yml'));
        $mergingArray = ['name' => $name, 'database' => ['host_uri' => $mongoHostname, 'db_name' => $mongoDatabase]];
        $newConfig = array_merge($currentConfig, $mergingArray);
        $yamlData = Yaml::dump($newConfig);
        file_put_contents(__DIR__.'/../Config/Config.yml', $yamlData);
        $output->writeln('<info>x</info> Configuration saved!');

        // oAuth scopes
        // @TODO retireve collection name from service
        $output->writeln('Setting up default OAuth scopes...');
        $mongo = new \API\Util\MongoClient($newConfig);
        $collection = $mongo->db->getCollection('authScopes');
        foreach ($newConfig['xAPI']['supported_auth_scopes'] as $scope) {
            $exists = $collection->find()->where('name', $scope['name'])->findOne();
            if ($exists) {
                $output->writeln('  - <comment>skip</comment> scope '.$exists->get('name').' exits already.');
            } else{
                $output->writeln('  - <info>new</info> scope '.$scope['name'].' added.');
            }
            $document = $collection->createDocument($scope);
            $document->save();
        }
        $output->writeln('<info>x</info> OAuth scopes configured!');

        $output->writeln('<info>Setup complete!</info>');

    }
}
