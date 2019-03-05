<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use React\ChildProcess\Process;

class ImageMagicReactProcessBuilder
{
    const DEFAULT_LIMIT = 10;

    private $resize = [];

    /** @var array */
    private $imageOptions = [];

    /** @var int */
    private $limit;

    /** @var string */
    private $imageCommandOptions;

    /** @var string */
    private $basePath;

    public function __construct(string $imageCommandOptions, string $basePath, int $limit)
    {
        $this->limit = $limit;
        $this->imageCommandOptions = $imageCommandOptions;
        $this->basePath = $basePath;
    }

    public static function create(int $limit = self::DEFAULT_LIMIT, array $imageOptions = []): self
    {
        return new self(
            self::buildImageMagicResizeOptions($imageOptions),
            rtrim($imageOptions['path'] ?? '', DIRECTORY_SEPARATOR),
            $limit
        );
    }

    private static function buildImageMagicResizeOptions(array $imageOptions): string
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

    public function withResize(string $source, string $target, int $width, int $height): self
    {
        $builder = clone $this;
        $builder->resize[$source][$target] = [$width, $height, $target];
        return $builder;
    }

    public function build(): Process
    {
        $path = $this->basePath;

        $commands = [];
        foreach ($this->resize as $source => $variations) {
            $sizes = [];
            usort($variations, [$this, 'variationComparator']);
            foreach ($variations as list($width, $height, $target)) {
                $sizes[] = sprintf('-resize %dx%d%s -write %s', $width, $height, $this->imageCommandOptions, $this->stripPath($target, $path));
            }

            $commands[] = sprintf(
                ' \\( %s -colorspace sRGB %s \\)',
                $this->stripPath($source, $path),
                implode(' ', $sizes)
            );
        }


        return new Process(sprintf('convert%s null:', implode('', $commands)), $path ?: null);
    }

    private function stripPath($path, $basePath): string
    {
        $basePath = $this->basePath ? $this->basePath . DIRECTORY_SEPARATOR : '';

        if ($basePath && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return $path;
    }

    public function isFull(): bool
    {
        return count($this->resize) >= $this->limit;
    }

    private function variationComparator(array $left, array $right): int
    {
        $leftValue = $left[0]*$left[1];
        $rightValue = $right[0]*$right[1];

        if ($leftValue === $rightValue) {
            return 0;
        }

        return $leftValue > $rightValue ? -1 : 1;
    }
}
