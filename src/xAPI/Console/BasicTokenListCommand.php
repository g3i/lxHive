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
use API\Service\Auth\Basic as AccessTokenService;

class BasicTokenListCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('auth:basic:list')
            ->setDescription('List tokens')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $accessTokenService = new AccessTokenService($this->getSlim());

        $accessTokenService->fetchTokens();

        $textArray = [];
        foreach ($accessTokenService->getCursor() as $document) {
            $textArray[] = $document->jsonSerialize();
        }

        $text = json_encode($textArray, JSON_PRETTY_PRINT);

        $output->writeln('<info>Tokens successfully fetched!</info>');
        $output->writeln('<info>Info:</info>');
        $output->writeln($text);
    }
}
