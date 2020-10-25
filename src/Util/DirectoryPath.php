<?php

namespace Mcustiel\Phiremock\Codeception\Util;

use Codeception\Configuration;
use Codeception\Exception\ConfigurationException;

class DirectoryPath
{
    /** @var string */
    private $path;

    public function __construct(string $path)
    {
        $this->ensureDirectoryExists($path);
        $this->path = $path;
    }

    public function asString(): string
    {
        return $this->path;
    }

    public static function createAndGetInstance(string $path): self
    {
        if (!is_dir($path)) {
            $created = mkdir($path);
            if (!$created) {
                throw new \RuntimeException('Could not create directory ' . $path);
            }
        }
        return new self($path);
    }

    public static function createAbsoluteOrRelativeToCodeceptionDir(string $path): self
    {
        if (substr($path, 0, 1) === '/') {
            return new self($path);
        }
        return new self(Configuration::projectDir() . $path);
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            throw new ConfigurationException('Could not find the configured expectations path: ' . $path);
        }
    }
}
