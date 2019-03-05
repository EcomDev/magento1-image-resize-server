<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class ImageGatewayBuilder
{
    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var string
     */
    private $pathPattern;

    /**
     * @var string
     */
    private $savePath = '';

    /**
     * @var string
     */
    private $baseUrl = '';

    public function __construct(string $sourcePath, string $pathPattern)
    {

        $this->sourcePath = $sourcePath;
        $this->pathPattern = $pathPattern;
    }

    public function withSourcePath(string $sourcePath): self
    {
        $builder = clone $this;
        $builder->sourcePath = rtrim($sourcePath, DIRECTORY_SEPARATOR);
        return $builder;
    }

    public function withSavePath(string $savePath): self
    {
        $builder = clone $this;
        $builder->savePath = rtrim($savePath, DIRECTORY_SEPARATOR);
        return $builder;
    }

    public function withPathPattern(string $pathPattern): self
    {
        $builder = clone $this;
        $builder->pathPattern = $pathPattern;
        return $builder;
    }

    public function withBaseUrl(string $baseUrl): self
    {
        $builder = clone $this;
        $builder->baseUrl = $baseUrl;
        return $builder;
    }

    public static function createDefault()
    {
        return new self(getcwd(), '/cache/:width:x:height:/:image:');
    }

    public function build(): ImageGateway
    {
        return new ImageGateway(
            new ImageFactory(),
            PathParser::create($this->pathPattern, $this->baseUrl),
            $this->sourcePath,
            $this->savePath
        );
    }
}
