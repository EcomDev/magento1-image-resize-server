<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

interface FileFinderObserver
{
    public function handleFoundFile(FileFinder $finder, string $fileName): void;

    public function handleMissingFile(FileFinder $finder, string $fileName): void;
}
