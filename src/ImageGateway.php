<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

class ImageGateway
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var string
     */
    private $rootDirectory;

    /** @var PathParser */
    private $pathParser;

    /**
     * Custom save path
     * @var string
     */
    private $savePath = '';

    /**
     *
     */
    public function __construct(
        ImageFactory $imageFactory,
        PathParser $pathParser,
        string $rootDirectory,
        string $savePath = ''
    ) {
        $this->imageFactory = $imageFactory;
        $this->rootDirectory = $rootDirectory;
        $this->pathParser = $pathParser;
        $this->savePath = $savePath;
    }

    public static function create(string $sourceDirectory, PathParser $pathParser)
    {
        return new self(
            new ImageFactory(),
            $pathParser,
            rtrim($sourceDirectory, DIRECTORY_SEPARATOR)
        );
    }

    public function findImage(string $path): Image
    {
        $imageInfo = $this->pathParser->parse($path);

        return $this->imageFactory->create(
            $this->resolvePath($imageInfo['path']),
            $this->filePathInRoot($imageInfo['source']),
            (int) $imageInfo['width'],
            (int) ($imageInfo['height'] ?: $imageInfo['width'])
        );
    }

    private function resolvePath(string $path): string
    {
        if ($this->savePath) {
            return sprintf('%s%s%s', $this->savePath, DIRECTORY_SEPARATOR, $path);
        }
        return $this->filePathInRoot($path);
    }

    public function withSavePath(string $savePath): self
    {
        $gateway = clone $this;
        $gateway->savePath = rtrim($savePath, DIRECTORY_SEPARATOR);
        return $gateway;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function filePathInRoot(string $path): string
    {
        return sprintf('%s%s%s', $this->rootDirectory, DIRECTORY_SEPARATOR, $path);
    }
}
