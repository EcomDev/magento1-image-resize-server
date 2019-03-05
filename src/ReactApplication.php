<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Response;
use React\Promise\Promise;
use function React\Promise\resolve;

class ReactApplication implements Application, ImageObserver, FileReaderObserver
{
    /** @var LoopInterface */
    private $loop;

    /** @var ReactHttpServerFactory */
    private $httpServerFactory;
    /**
     * @var ImageGateway
     */
    private $imageGateway;
    /**
     * @var FileFinder
     */
    private $fileFinder;
    /**
     * @var FileReader
     */
    private $fileReader;
    /**
     * @var ResizeQueue
     */
    private $resizeQueue;

    /** @var callable[] */
    private $images = [];

    /** @var callable[][] */
    private $fileReads = [];

    public function __construct(
        LoopInterface $loop,
        ReactHttpServerFactory $httpServerFactory,
        ImageGateway $imageGateway,
        FileFinder $fileFinder,
        FileReader $fileReader,
        ResizeQueue $resizeQueue
    ) {
        $this->loop = $loop;
        $this->httpServerFactory = $httpServerFactory;
        $this->imageGateway = $imageGateway;
        $this->fileFinder = $fileFinder;
        $this->fileReader = $fileReader;
        $this->resizeQueue = $resizeQueue;
    }

    public function run(): void
    {
        $this->httpServerFactory->createServer($this);
        $this->loop->addSignal(SIGINT, function () {
            $this->loop->stop();
        });
        $this->loop->run();
    }

    public function __invoke(ServerRequestInterface $request)
    {
        try {
            $image = $this->imageGateway->findImage($request->getUri()->getPath());

            return new Promise(function ($resolve) use ($image) {
                $this->images[spl_object_hash($image)] = $resolve;
                $image->validate($this->fileFinder, $this);
            });

        } catch (NotValidPathException $exception) {
            return $this->createNotFoundResponse();
        }
    }

    public function handleImageDelivery(Image $image, string $filePath)
    {
        $resolver = $this->images[spl_object_hash($image)] ?? [];
        unset($this->images[spl_object_hash($image)]);

        if ($resolver) {
            if (!isset($this->fileReads[$filePath])) {
                $this->fileReader->readFile($filePath, $this);
            }

            $this->fileReads[$filePath][] = $resolver;
        }
    }

    public function handleMissingImage(Image $image, string $filePath)
    {
        $resolver = $this->images[spl_object_hash($image)] ?? [];
        unset($this->images[spl_object_hash($image)]);
        if ($resolver) {
            $resolver($this->createNotFoundResponse());
        }
    }

    public function handleImageResize(Image $image)
    {
        $image->resize($this->resizeQueue);
    }

    /**
     *
     * @return Response
     */
    private function createNotFoundResponse(): Response
    {
        return new Response(404, ['Content-Type' => 'text/plain'], 'Image not found');
    }

    /**
     * Handle open file for read
     *
     * @param resource $readStream
     */
    public function handleFileRead(string $fileName, int $size, $readStream): void
    {
        $resolvers = $this->fileReads[$fileName] ?? [];
        unset($this->fileReads[$fileName]);

        if ($resolvers) {
            $response = new Response(
                200,
                ['Content-Type' => 'image/jpeg', 'Content-Length' => $size],
                $readStream
            );

            foreach ($resolvers as $resolver) {
                $resolver($response);
            }
        }
    }

    /**
     * Handle failed to read file
     *
     */
    public function handleFileReadError(string $fileName): void
    {
        $resolvers = $this->fileReads[$fileName] ?? [];
        unset($this->fileReads[$fileName]);

        if ($resolvers) {
            foreach ($resolvers as $resolver) {
                $resolver($this->createNotFoundResponse());
            }
        }
    }
}
