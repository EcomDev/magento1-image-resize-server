<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;

class ReactFileAdapterFactory
{
    /** @var LoopInterface */
    private $loop;

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
        return new ReactFileAdapter(Filesystem::create($this->loop));
    }

    public function createFinder(): FileFinder
    {
        return new ReactFileAdapter(Filesystem::create($this->loop));
    }
}
