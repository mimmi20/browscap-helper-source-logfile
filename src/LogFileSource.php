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
namespace BrowscapHelper\Source;

use BrowscapHelper\Source\Helper\FilePath;
use BrowscapHelper\Source\Reader\LogFileReader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UaResult\Browser\Browser;
use UaResult\Device\Device;
use UaResult\Engine\Engine;
use UaResult\Os\Os;
use UaResult\Result\Result;
use Wurfl\Request\GenericRequestFactory;

/**
 * Class DirectorySource
 *
 * @author  Thomas Mueller <mimmi20@live.de>
 */
class LogFileSource implements SourceInterface
{
    /**
     * @var string|null
     */
    private $sourcesDirectory = null;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * @param \Psr\Log\LoggerInterface                          $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $sourcesDirectory
     */
    public function __construct(LoggerInterface $logger, OutputInterface $output, $sourcesDirectory)
    {
        $this->logger           = $logger;
        $this->output           = $output;
        $this->sourcesDirectory = $sourcesDirectory;
    }

    /**
     * @param int $limit
     *
     * @return string[]
     */
    public function getUserAgents($limit = 0)
    {
        $counter   = 0;
        $allAgents = [];

        foreach ($this->getAgents() as $agent) {
            if ($limit && $counter >= $limit) {
                return;
            }

            if (empty($agent)) {
                continue;
            }

            if (array_key_exists($agent, $allAgents)) {
                continue;
            }

            yield $agent;
            $allAgents[$agent] = 1;
            ++$counter;
        }
    }

    /**
     * @return \UaResult\Result\Result[]
     */
    public function getTests()
    {
        $allTests = [];

        foreach ($this->getAgents() as $agent) {
            if (empty($agent)) {
                continue;
            }

            if (array_key_exists($agent, $allTests)) {
                continue;
            }

            $request  = (new GenericRequestFactory())->createRequestForUserAgent($agent);
            $browser  = new Browser(null);
            $device   = new Device(null, null);
            $platform = new Os(null, null);
            $engine   = new Engine(null);

            yield $agent => new Result($request, $device, $platform, $browser, $engine);
            $allTests[$agent] = 1;
        }
    }

    /**
     * @return array
     */
    private function loadFromPath()
    {
        $files          = scandir($this->sourcesDirectory, SCANDIR_SORT_ASCENDING);
        $filepathHelper = new FilePath();
        $fileCounter    = 0;

        foreach ($files as $filename) {
            /** @var $file \SplFileInfo */
            $file = new \SplFileInfo($this->sourcesDirectory . $filename);

            ++$fileCounter;

            $this->output->write('    reading file ' . $file->getPathname(), false);

            if (!$file->isFile() || !$file->isReadable()) {
                $this->output->writeln(' - skipped');

                continue;
            }

            $excludedExtensions = ['filepart', 'sql', 'rename', 'txt', 'zip', 'rar', 'php', 'gitkeep'];

            if (in_array($file->getExtension(), $excludedExtensions)) {
                $this->output->writeln(' - skipped');

                continue;
            }

            if (null === ($filepath = $filepathHelper->getPath($file))) {
                $this->output->writeln(' - skipped');

                continue;
            }

            $this->output->writeln('');

            yield $filepath;
        }
    }

    /**
     * @return string[]
     */
    private function getAgents()
    {
        $reader = new LogFileReader();

        /*******************************************************************************
         * loading files
         ******************************************************************************/

        foreach ($this->loadFromPath() as $filepath) {
            $reader->setLocalFile($filepath);

            foreach ($reader->getAgents($this->output) as $agentOfLine) {
                yield $agentOfLine;
            }
        }
    }
}
