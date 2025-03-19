<?php

/*
 * This file is part of lxHive LRS - http://lxhive.org/
 *
 * Copyright (C) 2017 G3 International
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
use API\Admin;

class OAuthClientCreateCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('oauth:client:create')
            ->setDescription('Creates a new OAuth client')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authAdmin = new Auth($this->getContainer());
        $validator = new Admin\Validator();

        // 1. name
        $helper = $this->getHelper('question');
        $question = new Question('Please enter a name: ', '');
        $question->setMaxAttempts(null);
        $question->setValidator(function ($answer) use ($validator) {
            $validator->validateName($answer);
            return $answer;
        });
        $name = $helper->ask($input, $output, $question);

        // 2. description
        $helper = $this->getHelper('question');
        $question = new Question('Please enter a description: ', '');
        $description = $helper->ask($input, $output, $question);

        // 3. redirect Uri
        $helper = $this->getHelper('question');
        $question = new Question('Please enter a redirect URI: ');
        $question->setMaxAttempts(null);
        $question->setValidator(function ($answer) use ($validator) {
            $validator->validateRedirectUri($answer);
            return $answer;
        });
        $redirectUri = $helper->ask($input, $output, $question);

        // 4. write record
        $client = $authAdmin->addOAuthClient($name, $description, $redirectUri);
        $text = json_encode($client, JSON_PRETTY_PRINT);

        $output->writeln('<info>OAuth client successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);

        return Command::SUCCESS;
    }
}
