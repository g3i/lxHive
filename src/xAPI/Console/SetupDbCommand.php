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

namespace API\Console;

use API\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use Sokil\Mongo\Client;

class SetupDbCommand extends Command
{
    //@TODO: such data and the yaml methods need to be sourced out into Config and Admi API's
    private $configDir;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->configDir = __DIR__.'/../Config';
        parent::__construct();
    }

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

        if ($this->checkYaml('Config.yml')) {
            $output->writeln('<error>A `Config.yml` file exists already. The LRS configuration would be overwritten. To restore the defaults you must manually remove the file first.</error>');

            return;
        }

        $helper = $this->getHelper('question');
        $question = new Question('Enter a name for this lxHive instance: ', 'Untitled');
        $name = $helper->ask($input, $output, $question);

        $connectionSuccess = false;
        while (!$connectionSuccess) {
            $question = new Question('Enter the URI of your MongoDB installation (default: "mongodb://127.0.0.1"): ', 'mongodb://127.0.0.1');
            $mongoHostname = $helper->ask($input, $output, $question);

            $client = new Client($mongoHostname);
            try {
                $mongoVersion = $client->getDbVersion();
                $output->writeln('Connection successful, MongoDB version '.$mongoVersion.'.');
                $connectionSuccess = true;
            } catch (\MongoConnectionException $e) {
                $output->writeln('Connection unsuccessful, please try again.');
            }
        }

        $question = new Question('Enter the name of your MongoDB database (default: "lxHive"): ', 'lxHive');
        $mongoDatabase = $helper->ask($input, $output, $question);

        $mergeConfig = ['name' => $name, 'database' => ['host_uri' => $mongoHostname, 'db_name' => $mongoDatabase]];
        $this->installYaml('Config.yml', $mergeConfig);

        if (!$this->checkYaml('Config.production.yml')) {
            $this->installYaml('Config.production.yml');
        }
        if (!$this->checkYaml('Config.development.yml')) {
            $this->installYaml('Config.development.yml');
        }

        $output->writeln('<info>Configuration saved!</info>');
        $output->writeln('<info>DB setup complete!</info>');
    }

    /**
     * checks if a yaml config file exists already in /src/xAPI/Config/.
     *
     * @param string $configYML yaml file
     *
     * @return bool
     */
    public function checkYaml($configYML)
    {
        return file_exists($configYML = $this->configDir.'/'.$configYML);
    }

    /**
     * creates a config yml file in /src/xAPI/Config/ from an existing template, merges data with template data.
     *
     * @param string $yaml      yaml file to be created from template
     * @param array  $mergeData associative array of config data to be merged in to the new config file
     *
     * @throws \Exception
     */
    public function installYaml($yml, array $mergeData = [])
    {
        $configYML = $this->configDir.'/'.$yml;
        $templateYML = $this->configDir.'/Templates/'.$yml;

        $template = file_get_contents($templateYML);
        if (false === $template) {
            throw new \Exception('Error reading file `'.$templateYML.'` Make sure the file exists and is readable.');
        }
        $data = Yaml::parse($template, true);// exceptionOnInvalidType
        if (!empty($mergeData)) {
            $data += $mergeData;
        }
        $ymlData = Yaml::dump($data, 3, 4);// exceptionOnInvalidType
        if (false === file_put_contents($configYML, $ymlData)) {
            throw new \Exception('Error rwriting '.__DIR__.'/../Config/'.$configYML.' Make sure the directory is writable.');
        }
    }
}
