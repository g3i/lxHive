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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use API\Service\Auth\Basic as AccessTokenService;

class BasicTokenExpireCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('auth:basic:expire')
            ->setDescription('Expires a token')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $accessTokenService = new AccessTokenService($this->getSlim());

        $accessTokenService->fetchTokens();
        $clientIds = [];
        foreach ($accessTokenService->getCursor() as $document) {
            $clientIds[] = $document->getClientId();
        }

        $question = new Question('Please enter the the client ID of the token you wish to delete: ');
        $question->setAutocompleterValues($clientIds);

        $clientId = $helper->ask($input, $output, $question);

        $question = new ConfirmationQuestion('Are you sure (y/n): ', false);

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $accessTokenService->expireToken($clientId);

        $output->writeln('<info>Token successfully expired!</info>');
    }
}
