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
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $authAdmin = new Auth($this->getContainer());
        $userAdmin = new User($this->getContainer());

        //TODO paginated
        $users = $userAdmin->fetchAllUserEmails();

        $output->writeln([
            '<info>==========================</>',
            '<info>Create a Basic token</>',
            '<info>==========================</>',
            '',
            '- Creating a basic tokens requires an already registered user (email address match).',
            '- Use the <info>./X user:create</info> console command for creating users.',
            '',
        ]);

        if (!count($users)) {
            throw new \RuntimeException('No registered users found');
        }

        // 1. email
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the email of the associated user: ', '');
        $question->setAutocompleterValues(array_keys($users));

        $question->setNormalizer(function ($value) {
            return $value ? trim(strtolower($value)) : '';
        });
        $question->setValidator(function ($answer) use ($users, $output) {
            if (!is_string($answer) || empty($answer)) {
                throw new \RuntimeException(
                    'Invalid input!'
                );
            }
            if ('exit' === $answer) {
                return $answer;
            }
            if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException(
                    'Invalid email address!'
                );
            }
            if (!isset($users[$answer])) {
                $output->writeln('  - Hint: Type <info>exit</info> to exit this dialog and return to the console.');
                throw new \RuntimeException(
                    'No user record for "'.$answer.'" found! '
                );
            }
            return $answer;
        });
        $question->setMaxAttempts(null);

        $email = $helper->ask($input, $output, $question);
        if ('exit' === $email) {
            $output->writeln('<error>Process aborted by user.</error>');
            return 0;
        }
        $user = $users[$email];

        // 2. Name
        $helper = $this->getHelper('question');
        $question = new Question('Please enter a name: ', 'untitled');
        $name = $helper->ask($input, $output, $question);

        // 3. description
        $helper = $this->getHelper('question');
        $question = new Question('Please enter a description: ', '');
        $description = $helper->ask($input, $output, $question);

        // 4. expiration period
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the expiration timestamp for the token (blank == indefinite): ');
        $expiresAt = $helper->ask($input, $output, $question);

        // 5. scopes
        $helper = $this->getHelper('question');
        $scopesDictionary = $userAdmin->fetchAvailablePermissions();
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

        // 6. store token
        $key = null;
        $secret = null;
        $token = $authAdmin->addToken($name, $description, $expiresAt, $user, $selectedScopes, $key, $secret);

        $text = json_encode($token, JSON_PRETTY_PRINT);

        $output->writeln('<info>Basic token successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }
}
