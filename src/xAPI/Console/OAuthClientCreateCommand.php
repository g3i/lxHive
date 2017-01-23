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

use API\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use API\Admin\Auth;

class OAuthClientCreateCommand extends Command
{
    /**
     * Auth Admin class.
     *
     * @var API\Admin\Auth
     */
    private $authAdmin;

    /**
     * Construct.
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->authAdmin = new Auth($container);
    }

    protected function configure()
    {
        $this
            ->setName('oauth:client:create')
            ->setDescription('Creates a new OAuth client')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $question = new Question('Please enter a name: ', 'untitled');
        $name = $helper->ask($input, $output, $question);

        $question = new Question('Please enter a description: ', '');
        $description = $helper->ask($input, $output, $question);

        $question = new Question('Please enter a redirect URI: ');
        $redirectUri = $helper->ask($input, $output, $question);

        $client = $this->getAuthAdmin()->addOAuthClient($name, $description, $redirectUri);
        $text = json_encode($client, JSON_PRETTY_PRINT);

        $output->writeln('<info>OAuth client successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }

    /**
     * Gets the Auth Admin class.
     *
     * @return API\Admin\Auth
     */
    public function getAuthAdmin()
    {
        return $this->authAdmin;
    }
}
