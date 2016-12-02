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
use API\Service\User as UserService;

class UserCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Creates a new user')
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption('email', 'e', InputOption::VALUE_OPTIONAL),
                    new InputOption('password', 'p', InputOption::VALUE_OPTIONAL),
                    new InputOption('permissions', 'pm', InputOption::VALUE_OPTIONAL),
                ))
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userService = new UserService($this->getSlim());

        $helper = $this->getHelper('question');

        if (null === $input->getOption('email')) {
            $question = new Question('Please enter an e-mail: ', 'untitled');
            $email = $helper->ask($input, $output, $question);
        } else {
            $email = $input->getOption('email');
        }

        if (null === $input->getOption('password')) {
            $question = new Question('Please enter a password: ', '');
            $password = $helper->ask($input, $output, $question);
        } else {
            $password = $input->getOption('password');
        }

        $userService->fetchAvailablePermissions();
        $permissionsDictionary = [];
        foreach ($userService->getCursor() as $permission) {
            $permissionsDictionary[$permission->getName()] = $permission;
        }

        if (null === $input->getOption('permissions')) {
            $question = new ChoiceQuestion(
                'Please select which permissions you would like to enable (defaults to super). Separate multiple values with commas (without spaces). If you select super, all other permissions are also inherited: ',
                array_keys($permissionsDictionary),
                '0'
            );
            $question->setMultiselect(true);

            $selectedPermissionNames = $helper->ask($input, $output, $question);
        } else {
            $selectedPermissionNames = explode(',', $input->getOption('permissions'));
        }

        $selectedPermissions = [];
        foreach ($selectedPermissionNames as $selectedPermissionName) {
            $selectedPermissions[] = $permissionsDictionary[$selectedPermissionName];
        }

        $user = $userService->addUser($email, $password, $selectedPermissions);
        $text = json_encode($user, JSON_PRETTY_PRINT);

        $output->writeln('<info>User successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }
}
