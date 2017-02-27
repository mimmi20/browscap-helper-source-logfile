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
class FilePath
{
    /**
     * @param \SplFileInfo $file
     *
     * @return string
     */
    public function getPath(\SplFileInfo $file)
    {
        $realpath = realpath($file->getPathname());

        if (false === $realpath) {
            return;
        }

        switch ($file->getExtension()) {
            case 'gz':
                $path = 'compress.zlib://' . $realpath;
                break;
            case 'bz2':
                $path = 'compress.bzip2://' . $realpath;
                break;
            case 'tgz':
                $path = 'phar://' . $realpath;
                break;
            default:
                $path = $realpath;
                break;
        }

        return $path;
    }
}
