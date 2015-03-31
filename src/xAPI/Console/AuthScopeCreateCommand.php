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
use API\Service\Auth\OAuth as OAuthService;

class AuthScopeCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('auth:scope:create')
            ->setDescription('Creates a new authentication scope!')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oAuthService = new OAuthService($this->getSlim());

        $helper = $this->getHelper('question');

        $question = new Question('Please enter a name (scope identifier): ', 'untitled');
        $name = $helper->ask($input, $output, $question);

        $question = new Question('Please enter a description: ', '');
        $description = $helper->ask($input, $output, $question);

        $scope = $oAuthService->addScope($name, $description);
        $text = json_encode($scope, JSON_PRETTY_PRINT);

        $output->writeln('<info>Auth scope successfully created!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }
}
