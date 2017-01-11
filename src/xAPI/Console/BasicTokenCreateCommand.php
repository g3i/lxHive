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
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use API\Admin\Auth;
use API\Admin\User;

class BasicTokenCreateCommand extends Command
{
    /**
     * Auth Admin class.
     *
     * @var API\Admin\Auth
     */
    private $authAdmin;

    /**
     * User Admin class.
     *
     * @var API\Admin\User
     */
    private $userAdmin;

    /**
     * Construct.
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->authAdmin = new Auth($container);
        $this->userAdmin = new User($container);
    }

    protected function configure()
    {
        $this
            ->setName('auth:basic:create')
            ->setDescription('Creates a new basic auth token')
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption('name', 'na', InputOption::VALUE_OPTIONAL),
                    new InputOption('description', 'd', InputOption::VALUE_OPTIONAL),
                    new InputOption('expiration', 'x', InputOption::VALUE_OPTIONAL),
                    new InputOption('email', 'e', InputOption::VALUE_OPTIONAL),
                    new InputOption('scopes', 's', InputOption::VALUE_OPTIONAL),
                    new InputOption('key', 'k', InputOption::VALUE_OPTIONAL),
                    new InputOption('secret', 'sc', InputOption::VALUE_OPTIONAL),
                ))
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $input->getOption('name')) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter a name: ', 'untitled');
            $name = $helper->ask($input, $output, $question);
        } else {
            $name = $input->getOption('name');
        }

        if (null === $input->getOption('description')) {
            $question = new Question('Please enter a description: ', '');
            $description = $helper->ask($input, $output, $question);
        } else {
            $description = $input->getOption('description');
        }

        if (null === $input->getOption('expiration')) {
            $question = new Question('Please enter the expiration timestamp for the token (blank == indefinite): ');
            $expiresAt = $helper->ask($input, $output, $question);
        } else {
            $expiresAt = $input->getOption('expiration');
        }

        $users = $this->getUserAdmin()->fetchAllUserEmails();

        if (null === $input->getOption('email')) {
            $question = new Question('Please enter enter the e-mail of the associated user: ', '');
            $question->setAutocompleterValues(array_keys($users));
            $email = $helper->ask($input, $output, $question);
            $user = $users[$email];
        } else {
            $email = $input->getOption('email');
            if (!isset($users[$email])) {
                throw new \Exception('Invalid e-mail provided! User does not exist!');
            }
            $user = $users[$email];
        }

        $scopesDictionary = $this->getUserAdmin()->fetchAvailablePermissions();

        if (null === $input->getOption('scopes')) {
            $question = new ChoiceQuestion(
                'Please select which scopes you would like to enable (defaults to super). Separate multiple values with commas (without spaces). If you select super, all other permissions are also inherited: ',
                array_keys($scopesDictionary),
                '0'
            );
            $question->setMultiselect(true);

            $selectedScopeNames = $helper->ask($input, $output, $question);

            $selectedScopes = [];
            foreach ($selectedScopeNames as $selectedScopeName) {
                $selectedScopes[] = $scopesDictionary[$selectedScopeName];
            }
        } else {
            $selectedScopeNames = explode(',', $input->getOption('scopes'));
        }

        $selectedScopes = [];
        foreach ($selectedScopeNames as $selectedScopeName) {
            $selectedScopes[] = $scopesDictionary[$selectedScopeName];
        }

        if (null !== $input->getOption('key')) {
            $key = $input->getOption('key');
        } else {
            $key = null;
        }

        if (null !== $input->getOption('secret')) {
            $secret = $input->getOption('secret');
        } else {
            $secret = null;
        }

        $this->getAuthAdmin()->addToken($name, $description, $expiresAt, $user, $selectedScopes, $key, $secret);

        $text = json_encode($token, JSON_PRETTY_PRINT);

        $output->writeln('<info>Basic token successfully created!</info>');
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

    /**
     * Gets the User Admin class.
     *
     * @return API\Admin\User
     */
    public function getUserAdmin()
    {
        return $this->userAdmin;
    }
}
