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
use API\Service\Auth\OAuth as OAuthService;

class SetupOAuthCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('setup:oauth')
            ->setDescription('Sets up default OAuth scopes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Setting up default OAuth scopes...</info>');

        $oAuthService = new OAuthService($this->getSlim());
        foreach ($this->getSlim()->config('xAPI')['supported_auth_scopes'] as $authScope) {
            $scope = $oAuthService->addScope($authScope['name'], $authScope['description']);
            if (!$scope) {
                $output->writeln('  - <comment>skip</comment> scope '.$authScope['name'].' exits already.');
            } else{
                $output->writeln('  - <info>new</info> scope '.$authScope['name'].' added.');
            }
        }

        $output->writeln('<info>OAuth scopes configured!</info>');
    }
}
