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
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
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
        $basicAuthService = new BasicAuthService($this->getSlim());

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

        $userService = new UserService($this->getSlim());
        $userService->fetchAll();
        $users = [];
        foreach ($userService->getCursor() as $user) {
            $users[$user->getEmail()] = $user;
        }

        if (null === $input->getOption('email')) {
            $question = new Question('Please enter enter the e-mail of the associated user: ', '');
            $question->setAutocompleterValues(array_keys($users));
            $email = $helper->ask($input, $output, $question);
            $user = $users[$email];
        } else {
            $email = $input->getOption('email');
            if (!isset($users[$email])) {
                throw new Exception('Invalid e-mail provided! User does not exist!');
            }
            $user = $users[$email];
        }

        $userService->fetchAvailablePermissions();
        $scopesDictionary = [];
        foreach ($userService->getCursor() as $scope) {
            $scopesDictionary[$scope->getName()] = $scope;
        }

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

        $token = $basicAuthService->addToken($name, $description, $expiresAt, $user, $selectedScopes);

        if (null !== $input->getOption('key')) {
            $token->setKey($input->getOption('key'));
            $token->save();
        }

        if (null !== $input->getOption('secret')) {
            $token->setSecret($input->getOption('secret'));
            $token->save();
        }

        $text = json_encode($token, JSON_PRETTY_PRINT);

        $output->writeln('<info>Basic token successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }
}
