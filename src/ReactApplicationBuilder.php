<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
use React\Filesystem\FilesystemInterface;

class ReactApplicationBuilder implements ApplicationBuilder
{
    /** @var LoopInterface */
    private $loop;

    /** @var FilesystemInterface */
    private $fileAdapterFactory;

    /** @var ReactHttpServerFactory */
    private $httpServerFactory;
    /**
     * @var ImageGatewayBuilder
     */
    private $gatewayBuilder;
    /**
     * @var ImageMagicReactResizeServiceBuilder
     */
    private $resizeServiceBuilder;
    /**
     * @var ReactBackgroundResizeQueueFactory
     */
    private $resizeQueueFactory;

    public function __construct(
        LoopInterface $loop,
        ReactFileAdapterFactory $fileAdapterFactory,
        ReactHttpServerFactory $httpServerFactory,
        ImageGatewayBuilder $gatewayBuilder,
        ImageMagicReactResizeServiceBuilder $resizeServiceBuilder,
        ReactBackgroundResizeQueueFactory $resizeQueueFactory
    ) {
        $this->loop = $loop;
        $this->fileAdapterFactory = $fileAdapterFactory;
        $this->httpServerFactory = $httpServerFactory;
        $this->gatewayBuilder = $gatewayBuilder;
        $this->resizeServiceBuilder = $resizeServiceBuilder;
        $this->resizeQueueFactory = $resizeQueueFactory;
    }

    public static function create(int $port)
    {
        $loop = Factory::create();
        return new self(
            $loop,
            ReactFileAdapterFactory::createFromLoop($loop),
            ReactHttpServerFactory::createFromLoopWithPort($loop, $port),
            ImageGatewayBuilder::createDefault(),
            new ImageMagicReactResizeServiceBuilder($loop, new ImageMagicReactProcessBuilderFactory()),
            new ReactBackgroundResizeQueueFactory()
        );
    }

    public function withBaseUrl(string $baseUrl): ApplicationBuilder
    {
        $builder = clone $this;
        $builder->gatewayBuilder = $this->gatewayBuilder->withBaseUrl($baseUrl);
        return $builder;
    }

    public function withSavePath(string $savePath): ApplicationBuilder
    {
        $builder = clone $this;
        $builder->gatewayBuilder = $this->gatewayBuilder->withSavePath($savePath);
        $builder->resizeServiceBuilder = $this->resizeServiceBuilder->withBaseDirectory($savePath);
        return $builder;
    }

    public function withSourcePath(string $sourcePath): ApplicationBuilder
    {
        $builder = clone $this;
        $builder->gatewayBuilder = $this->gatewayBuilder->withSourcePath($sourcePath);
        return $builder;
    }

    public function withUrlPattern(string $urlPattern): ApplicationBuilder
    {
        $builder = clone $this;
        $builder->gatewayBuilder = $this->gatewayBuilder->withPathPattern($urlPattern);
        return $builder;
    }

    public function withWorkerLimit(int $workerLimit): ApplicationBuilder
    {
        $builder = clone $this;
        $builder->resizeServiceBuilder = $this->resizeServiceBuilder->withWorkerLimit($workerLimit);
        return $builder;
    }

    public function withWorkerImageLimit(int $imageLimit): ApplicationBuilder
    {
        $builder = clone $this;
        $builder->resizeServiceBuilder = $this->resizeServiceBuilder->withWorkerImageLimit($imageLimit);
        return $builder;
    }

    public function withResizeOptions(array $options): ApplicationBuilder
    {
        $builder = clone $this;
        $builder->resizeServiceBuilder = $this->resizeServiceBuilder->withResizeOptions($options);
        return $this;
    }

    public function build(): Application
    {
        return new ReactApplication(
            $this->loop,
            $this->httpServerFactory,
            $this->gatewayBuilder->build(),
            $this->fileAdapterFactory->createFinder(),
            $this->fileAdapterFactory->createReader(),
            $this->resizeQueueFactory->createForLoop($this->loop, $this->resizeServiceBuilder->build())
        );
    }
}
