<?php
/**
 * This file is part of the browscap-helper-source-logfile package.
 *
 * Copyright (c) 2016-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace BrowscapHelper\Source\Helper;

/**
 * Class DiffCommand
 *
 * @category   Browscap
 *
 * @author     James Titcumb <james@asgrim.com>
 */
class Sorter
{
    /**
     * @param array $agents
     *
     * @return array
     */
    public function sortAgents(array $agents)
    {
        $sortCount = [];
        $sortAgent = [];

        foreach ($agents as $agentOfLine => $count) {
            $sortCount[$agentOfLine] = $count;
            $sortAgent[$agentOfLine] = $agentOfLine;
        }

        array_multisort($sortCount, SORT_DESC, $sortAgent, SORT_ASC, $agents);

        return $agents;
    }
}
