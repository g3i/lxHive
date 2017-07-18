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

use API\Admin;
use API\Admin\User as UserAdministration;

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
        $userAdmin = new UserAdministration($this->getContainer());
        $helper = $this->getHelper('question');
        $validator = new Admin\Validator();

        // 1. Name
        $question = new Question('Please enter a name: ', '');
        $question->setMaxAttempts(null);
        $question->setValidator(function ($answer) use ($validator) {
            $validator->validateName($answer);
            return $answer;
        });
        $name = $helper->ask($input, $output, $question);

        // 2. Description
        $question = new Question('Please enter a description: ', '');
        $description = $helper->ask($input, $output, $question);

        // 3. Email
        $question = new Question('Please enter an e-mail: ', '');
        $question->setMaxAttempts(null);
        $question->setValidator(function ($answer) use ($userAdmin) {
            $userAdmin->validateUserEmail($answer);
            return $answer;
        });
        $email = $helper->ask($input, $output, $question);

        // 4. Password
        $question = new Question('Please enter a password: ', '');
        $question->setMaxAttempts(null);
        $question->setValidator(function ($answer) use ($validator) {
            $validator->validatePassword($answer);
            return $answer;
        });
        $password = $helper->ask($input, $output, $question);

        // 5. Permissions
        $available = $userAdmin->fetchAvailablePermissionNames();
        $question = new ChoiceQuestion(
            'Please select which permissions you would like to enable (defaults to super). Separate multiple values with commas (without spaces). If you select super, all other permissions are also inherited: ',
            $available,
            '0'
        );
        $question->setMultiselect(true);
        $question->setMaxAttempts(null);
        $permissions = $helper->ask($input, $output, $question);// validation by ChoiceQuestion

        // 6. add record
        $permissions = array_unique($permissions);
        $user = $userAdmin->addUser($name, $description, $email, $password, $permissions);
        $text = json_encode($user, JSON_PRETTY_PRINT);

        $output->writeln('<info>User successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }
}
