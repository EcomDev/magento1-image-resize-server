<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


use React\EventLoop\LoopInterface;
use React\Http\StreamingServer;
use React\Socket\Server;

class ReactHttpServerFactory
{
    /** @var Server */
    private $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function createServer(callable $requestHandler): StreamingServer
    {
        $httpServer = new StreamingServer($requestHandler);
        $httpServer->listen($this->server);
        return $httpServer;
    }

    public static function createFromLoopWithPort(LoopInterface $loop, int $port)
    {
        return new self(new Server($port, $loop, ['so_reuseport' => true, 'backlog' => 200]));
    }
}
