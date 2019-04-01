<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class ImageMagicReactProcessBuilderFactory implements ReactChildProcessBuilderFactory
{

    public function create(int $resizeLimit, array $imageOptions = []): ReactChildProcessBuilder
    {
        return new ImageMagicReactProcessBuilder(
            $this->buildImageMagicResizeOptions($imageOptions),
            rtrim($imageOptions['path'] ?? '', DIRECTORY_SEPARATOR),
            $resizeLimit
        );
    }

    private function buildImageMagicResizeOptions(array $imageOptions): string
    {
        $optionMap = [
            'sampling' => ['-sampling-factor', false],
            'quality' => ['-quality', false],
            'interlace' => ['-interlace', false],
            'filter' => ['-filter', false],
            'strip' => ['-strip', true],
        ];

        $options = [];

        foreach ($optionMap as $key => list($name, $isBoolean)) {
            $value = $imageOptions[$key] ?? null;

            if ($value && !$isBoolean) {
                if (is_int($value)) {
                    $options[] = sprintf(' %s %d', $name, $value);
                } else {
                    $options[] = sprintf(' %s %s', $name, escapeshellarg($value));
                }
            } elseif ($value && $isBoolean) {
                $options[] = sprintf(' %s', $name);
            }
        }

        return implode('', $options);
    }
}
