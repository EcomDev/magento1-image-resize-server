<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
use React\Filesystem\FilesystemInterface;

class ReactFileAdapterFactory
{
    /** @var LoopInterface */
    private $loop;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public static function createFromLoop(LoopInterface $loop): self
    {
        return new self($loop);
    }

    public function createReader(): FileReader
    {
        return new ReactFileAdapter($this->createFileSystemOnlyOnce());
    }

    public function createFinder(): FileFinder
    {
        return new ReactFileAdapter($this->createFileSystemOnlyOnce());
    }

    private function createFileSystemOnlyOnce(): FilesystemInterface
    {
        if (!$this->filesystem) {
            $this->filesystem = Filesystem::create($this->loop);
        }

        return $this->filesystem;
    }
}
