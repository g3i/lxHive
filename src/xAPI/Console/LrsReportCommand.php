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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use API\Admin\LrsReport;

class LrsReportCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Runs a basic health report on your LRS')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $report = new LrsReport();
        $result = $report->check();

        $table = new Table($output);
        $table->setHeaders([
            'item', 'status', 'message', 'notes'
        ]);
        foreach($result as $title => $section){
            $this->renderTableSection($title, $section, $output, $table);
        }

        $table->render();
    }

    /**
     * Render a report section into a Symfony console table
     * @see LrsReport::check()
     *
     * @param string $caption section title
     * @param string $result LrsReport::check() result item
     * @param OutputInterface $output
     * @param Table $table
     *
     * @return void
     */
    protected function renderTableSection($caption, $result, OutputInterface $output, Table $table)
    {
        $table->addRow(new TableSeparator());
        $table->addRow([
            $this->style('caption', $caption)
        ]);
        $table->addRow(new TableSeparator());

        foreach($result as $item => $data){
            $table->addRow([
                $item,
                $this->style($data['status'], $data['status']),
                $data['value'],
                $data['note'],
            ]);
        }
    }

    /**
     * Styles a console message
     * @see http://symfony.com/doc/current/console/coloring.html
     *
     * @param string $status see \API\Admin\LrsReport
     * @param string $message
     * @return string
     */
    protected function style($status, $message)
    {

        switch ($status) {
            case 'caption': {
                return '<fg=cyan;options=bold>'.$message.'</>';
            }
            case 'success': {
                return '<fg=green;options=bold>'.$message.'</>';
            }
            case 'error': {
                return '<fg=red;options=bold>'.$message.'</>';
            }
            case 'warn': {
                return '<fg=yellow>'.$message.'</>';
            }
        }

        return $message;

    }
}
