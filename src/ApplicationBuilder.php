<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


interface ApplicationBuilder
{
    public function withBaseUrl(string $baseUrl): self;
    public function withSavePath(string $savePath): self;
    public function withSourcePath(string $sourcePath): self;
    public function withUrlPattern(string $urlPattern): self;
    public function withWorkerLimit(int $workerLimit): self;
    public function withWorkerImageLimit(int $imageLimit): self;
    public function withResizeOptions(array $options): self;

    public function build(): Application;
}
