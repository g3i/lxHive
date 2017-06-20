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

        // get permissions from user service. Abort if no permissions set
        $userService->fetchAvailablePermissions();
        $hasPermissions = $userService->getCursor()->count();
        if (!$hasPermissions) {
            throw new \RuntimeException(
                'No oAuth scopes found. Please run command <comment>setup:oauth</comment> first'
            );
        }

        $helper = $this->getHelper('question');

        $question = new Question('Please enter an e-mail: ', '');
        $question->setMaxAttempts(null);
        $question->setValidator(function ($answer) {
            $this->validateEmail($answer);
            return $answer;
        });
        $email = $helper->ask($input, $output, $question);

        $question = new Question('Please enter a password: ', '');
        $question->setMaxAttempts(null);
        $question->setValidator(function ($answer) {
            $this->validatePassword($answer);
            return $answer;
        });
        $password = $helper->ask($input, $output, $question);

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

        $userCount = $userService->getEmailCount($email);
        if ($userCount > 1) {
            $output->writeln('<comment>Note: there are ' . $userCount . ' duplicate accounts with the same email - ' . $email . '</comment>');
        }

        $output->writeln('<info>User successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }


    /**
     * Validate password
     * @param string $str
     *
     * @return void
     * @throws AdminException
     */
    public function validatePassword($str)
    {
        $errors = [];
        $length = 6;

        if (strlen($str) < $length) {
            $errors[] = 'Must have at least '.$length.' characters';
        }

        if (!preg_match('/[0-9]+/', $str)) {
            $errors[] = 'Must include at least one number.';
        }

        if (!preg_match('/[a-zA-Z]+/', $str)) {
            $errors[] = 'Must include at least one letter.';
        }

        if (!empty($errors)) {
            throw new \RuntimeException(json_encode($errors));
        }
    }

    /**
     * Validate email address
     * @param string $email
     *
     * @return void
     * @throws AdminException
     */
    public function validateEmail($email)
    {
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid email address!');
        }
    }
}
