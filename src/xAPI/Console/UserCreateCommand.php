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

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;

use Symfony\Component\Console\Command\Command as SymfonyCommand;

use API\Config;
use API\Bootstrap;
use API\Command;
use API\Admin\Setup;
use API\Admin\Validator;

use API\Admin;
use API\Admin\User as UserAdmin;

use RunTimeException;

class UserCreateCommand extends Command
{
    /**
     * @var UserAdmin $userAdmin;
     */
    private $userAdmin;

    /**
     * @var Validator $validator
     */
    private $validator;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Creates a new user')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('<info>Create a new user.</info>');
        $io->newLine();

        $this->userAdmin = new UserAdmin($this->getContainer());
        $this->validator = new Validator();

        // 1. Name
        $name = $io->ask('<comment>[1/6]</comment> Please enter a name', null, function ($answer) {
            $this->validator->validateName($answer);
            return $answer;
        });

        // 2. Description
        $description = $io->ask('<comment>[2/6]</comment> Please enter a description', false);

        // 3. Email
        $email = $io->ask('<comment>[3/6]</comment> Please enter an e-mail', null, function ($answer) {
            $this->validator->validateEmail($answer);
            return $answer;
        });

        // 4. Password
        $password = $io->askHidden('<comment>[4/6]</comment> Please enter a password', function ($answer) {
            $this->validator->validatePassword($answer);
            return $answer;
        });

        $confirmed = $io->askHidden('<comment>[5/6]</comment> Please confirm password', function ($answer) use ($password) {
            if ($answer !== $password) {
                throw new RuntimeException('Passwords do not match');
            }
            return $answer;
        });

        // 5. Permissions
        $available = $this->userAdmin->fetchAvailablePermissionNames();

        $question = new ChoiceQuestion(
            implode("\n", [
                '<comment>[6/6]</comment> Please select which permissions you would like to enable.',
                'Separate multiple values wit
                h commas (without spaces).',
               'If you select super, all other permissions are also inherited: ',
            ]),
            $available,
            '0'
        );
        $question->setMultiselect(true);
        $question->setMaxAttempts(null);
        $permissions  = $io->askQuestion($question);// validation by ChoiceQuestion

        // 6. add record
        $permissions = array_unique($permissions);
        $cursor = $this->userAdmin->addUser($name, $description, $email, $password, $permissions);

        $io->success('User successfully created!');

        // 7. display
        $io->text(' <error> !!! </error> Please store below user information privately and secure!');
        $io->newLine();

        $user = $cursor->toArray();

        $io->listing([
            '<comment>id</comment>: '.$cursor->getId(),
            '<comment>name</comment>: '.$user->name,
            '<comment>email</comment>: '.$user->email,
            '<comment>password</comment>: '.$password,
            '<comment>permissions</comment>: '.implode(', ', $permissions),
        ]);

        $io->text('<info> --> </info> NEXT: Create a basic token with <comment>./X auth:basic:create</comment>');
        $io->text('or');
        $io->text('<info> --> </info> NEXT: Create an oAuth token with <comment>./X oauth:client:create</comment>');
        $io->newLine();

    }
}
