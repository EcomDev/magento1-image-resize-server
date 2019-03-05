<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


interface FileReader
{
    public function readFile(string $filePath, FileReaderObserver $observer): void;
}
