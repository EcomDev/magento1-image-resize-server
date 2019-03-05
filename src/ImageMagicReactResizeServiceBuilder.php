<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

class ImageMagicReactResizeServiceBuilder
{
    private const DEFAULT_WORKER_LIMIT = 3;
    private const DEFAULT_IMAGE_LIMIT_PER_WORKER = 10;

    private $workerImageLimit = self::DEFAULT_IMAGE_LIMIT_PER_WORKER;
    private $workerLimit = self::DEFAULT_WORKER_LIMIT;
    private $resizeOptions = [];
    private $baseDirectory = '';

    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct(
        LoopInterface $loop
    ) {
        $this->loop = $loop;
    }

    public function withWorkerLimit(int $workerLimit): self
    {
        $builder = clone $this;
        $builder->workerLimit = $workerLimit;
        return $builder;
    }

    public function withWorkerImageLimit(int $imageLimit): self
    {
        $builder = clone $this;
        $builder->workerImageLimit = $imageLimit;
        return $builder;
    }

    public function withBaseDirectory(string $baseDirectory): self
    {
        $builder = clone $this;
        $builder->baseDirectory = $baseDirectory;
        return $builder;
    }

    public function withResizeOptions(array $options): self
    {
        $builder = clone $this;
        $builder->resizeOptions = $options + $this->resizeOptions;
        return $builder;
    }

    public function build(): ImageMagicReactResizeService
    {
        $resizeService = new ImageMagicReactResizeService(
            ImageMagicReactProcessBuilder::create(
                $this->workerImageLimit,
                ($this->baseDirectory ? ['path' => $this->baseDirectory] : []) + $this->resizeOptions
            ),
            $this->loop,
            $this->workerLimit
        );

        $this->loop->addPeriodicTimer(0.001, $resizeService);

        return $resizeService;
    }
}
