<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ImageResizeServer;


class PathParser
{
    /**
     * RegExp Pattern for path
     * @var string
     */
    private $pathPattern;
    /**
     * @var string
     */
    private $baseUrl;

    public function __construct(string $pathPattern, string $baseUrl)
    {
        $this->pathPattern = $pathPattern;

        $this->baseUrl = $baseUrl;
    }

    public static function create(string $pathPattern, string $baseUrl = ''): self
    {
        $pathPattern = self::formatPathPattern(
            ($baseUrl ? sprintf('%s/%s', rtrim($baseUrl, '/'), ltrim($pathPattern, '/')) :  $pathPattern)
        );

        return new self($pathPattern, $baseUrl);
    }

    /**
     * @param string $pathPattern
     *
     * @return string
     */
    private static function formatPathPattern(string $pathPattern): string
    {
        $placeholders = [
            ':width:' => uniqid('width'),
            ':height:' => uniqid('height'),
            ':image:' => uniqid('image'),
            ':any_dir:' => uniqid('any_dir')
        ];

        $patterns = [
            $placeholders[':width:'] => '(?<width>[0-9]*)',
            $placeholders[':height:'] => '(?<height>[0-9]*)',
            $placeholders[':image:'] => '(?<image>.*?)',
            $placeholders[':any_dir:'] => '[^/]+',
        ];

        $pathPattern = preg_quote(strtr($pathPattern, $placeholders), '#');

        $pathPattern = strtr($pathPattern, $patterns);
        $pathPattern = sprintf('#^%s$#', $pathPattern);

        return $pathPattern;
    }

    public function parse($path): array
    {

        if (!preg_match('#([:;]|/[\.]+/|\\\\)#', $path) && preg_match($this->pathPattern, $path, $matches)) {
            $relativePath = ltrim($this->baseUrl ? substr($path, strlen($this->baseUrl)) : $path, '/');
            return [
                'width' => $matches['width'],
                'height' => $matches['height'],
                'source' => $matches['image'],
                'path' => $relativePath
            ];
        }
        throw new NotValidPathException(sprintf('Request path is not valid "%s"', $path));
    }
}
