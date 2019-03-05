<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


interface FileReaderObserver
{
    /**
     * Handle open file for read
     *
     * @param resource $readStream
     */
    public function handleFileRead(string $fileName, int $size, $readStream): void;

    /**
     * Handle failed to read file
     *
     */
    public function handleFileReadError(string $fileName): void;
}
