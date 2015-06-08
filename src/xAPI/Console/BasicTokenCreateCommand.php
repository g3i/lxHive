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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use API\Service\Auth\Basic as BasicAuthService;
use API\Service\User as UserService;

class BasicTokenCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('auth:basic:create')
            ->setDescription('Creates a new basic auth token')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $basicAuthService = new BasicAuthService($this->getSlim());

        $helper = $this->getHelper('question');

        $question = new Question('Please enter a name: ', 'untitled');
        $name = $helper->ask($input, $output, $question);

        $question = new Question('Please enter a description: ', '');
        $description = $helper->ask($input, $output, $question);

        $question = new Question('Please enter the expiration timestamp for the token (blank == indefinite): ');
        $expiresAt = $helper->ask($input, $output, $question);

        $userService = new UserService($this->getSlim());
        $userService->fetchAll();
        $users = [];
        foreach ($userService->getCursor() as $user) {
            $users[$user->getEmail()] = $user;
        }
        $question = new Question('Please enter enter the e-mail of the associated user: ', '');
        $question->setAutocompleterValues(array_keys($users));
        $email = $helper->ask($input, $output, $question);
        $user = $users[$email];

        $userService->fetchAvailablePermissions();
        $scopesDictionary = [];
        foreach ($userService->getCursor() as $scope) {
            $scopesDictionary[$scope->getName()] = $scope;
        }

        $question = new ChoiceQuestion(
            'Please select which scopes you would like to enable (defaults to super). If you select super, all other permissions are also inherited: ',
            array_keys($scopesDictionary),
            '0'
        );
        $question->setMultiselect(true);

        $selectedScopeNames = $helper->ask($input, $output, $question);

        $selectedScopes = [];
        foreach ($selectedScopeNames as $selectedScopeName) {
            $selectedScopes[] = $scopesDictionary[$selectedScopeName];
        }

        $token = $basicAuthService->addToken($name, $description, $expiresAt, $user, $selectedScopes);
        $text  = json_encode($token, JSON_PRETTY_PRINT);

        $output->writeln('<info>Basic token successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }
}
