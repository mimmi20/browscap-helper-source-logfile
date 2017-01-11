<?php

namespace BrowscapHelper\Source;

use BrowscapHelper\Source\Helper\FilePath;
use BrowscapHelper\Source\Reader\LogFileReader;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param string $sourcesDirectory
     */
    public function __construct($sourcesDirectory)
    {
        $this->sourcesDirectory = $sourcesDirectory;
    }

    /**
     * @param \Monolog\Logger                                   $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int                                               $limit
     *
     * @return \Generator
     */
    public function getUserAgents(Logger $logger, OutputInterface $output, $limit = 0)
    {
        $counter   = 0;
        $allAgents = [];

        foreach ($this->getAgents($output) as $agent) {
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
     * @param \Monolog\Logger                                   $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Generator
     */
    public function getTests(Logger $logger, OutputInterface $output)
    {
        $allTests = [];

        foreach ($this->getAgents($output) as $agent) {
            if (empty($agent)) {
                continue;
            }

            if (array_key_exists($agent, $allTests)) {
                continue;
            }

            $test = [
                'ua'         => $agent,
                'properties' => [
                    'Browser_Name'            => null,
                    'Browser_Type'            => null,
                    'Browser_Bits'            => null,
                    'Browser_Maker'           => null,
                    'Browser_Modus'           => null,
                    'Browser_Version'         => null,
                    'Platform_Codename'       => null,
                    'Platform_Marketingname'  => null,
                    'Platform_Version'        => null,
                    'Platform_Bits'           => null,
                    'Platform_Maker'          => null,
                    'Platform_Brand_Name'     => null,
                    'Device_Name'             => null,
                    'Device_Maker'            => null,
                    'Device_Type'             => null,
                    'Device_Pointing_Method'  => null,
                    'Device_Dual_Orientation' => null,
                    'Device_Code_Name'        => null,
                    'Device_Brand_Name'       => null,
                    'RenderingEngine_Name'    => null,
                    'RenderingEngine_Version' => null,
                    'RenderingEngine_Maker'   => null,
                ],
            ];

            yield [$agent => $test];
            $allTests[$agent] = 1;
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Generator
     */
    private function loadFromPath(OutputInterface $output = null)
    {
        $files          = scandir($this->sourcesDirectory, SCANDIR_SORT_ASCENDING);
        $filepathHelper = new FilePath();
        $fileCounter    = 0;

        foreach ($files as $filename) {
            /** @var $file \SplFileInfo */
            $file = new \SplFileInfo($this->sourcesDirectory . $filename);

            ++$fileCounter;

            if (!$file->isFile() || !$file->isReadable()) {
                $output->writeln(' - skipped');

                continue;
            }

            $excludedExtensions = ['filepart', 'sql', 'rename', 'txt', 'zip', 'rar', 'php', 'gitkeep'];

            if (in_array($file->getExtension(), $excludedExtensions)) {
                $output->writeln(' - skipped');

                continue;
            }

            if (null === ($filepath = $filepathHelper->getPath($file))) {
                $output->writeln(' - skipped');

                continue;
            }

            yield $filepath;
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Generator
     */
    private function getAgents(OutputInterface $output = null)
    {
        $reader = new LogFileReader();

        /*******************************************************************************
         * loading files
         ******************************************************************************/

        foreach ($this->loadFromPath($output) as $filepath) {
            $reader->setLocalFile($filepath);

            foreach ($reader->getAgents($output) as $agentOfLine) {
                yield $agentOfLine;
            }
        }
    }
}
