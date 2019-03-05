<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class InMemoryFileFinder implements FileFinder
{
    private $files = [];

    public function withFile(string $fileName): self
    {
        $finder = clone $this;
        $finder->files[$fileName] = true;
        return $finder;
    }

    public function withoutFile(string $fileName): self
    {
        $finder = clone $this;
        unset($finder->files[$fileName]);
        return $finder;
    }

    public function findFile(string $fileName, FileFinderObserver $observer): void
    {
        if (isset($this->files[$fileName])) {
            $observer->handleFoundFile($this, $fileName);
            return;
        }

        $observer->handleMissingFile($this, $fileName);
    }
}
