<?php

/**
 * This file is part of phiremock-codeception-extension.
 *
 * phiremock-codeception-extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * phiremock-codeception-extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with phiremock-codeception-extension.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Mcustiel\Phiremock\Codeception\Util;

use Codeception\Configuration;
use Codeception\Exception\ConfigurationException;
use RuntimeException;

class DirectoryPath
{
    /** @var string */
    private $path;

    /** @throws ConfigurationException */
    public function __construct(string $path)
    {
        $this->ensureDirectoryExists($path);
        $this->path = $path;
    }

    public function asString(): string
    {
        return $this->path;
    }

    /** @throws ConfigurationException */
    public static function createAndGetInstance(string $path): self
    {
        if (!is_dir($path)) {
            $created = mkdir($path);
            if (!$created) {
                throw new RuntimeException('Could not create directory ' . $path);
            }
        }
        return new self($path);
    }

    /** @throws ConfigurationException */
    public static function createAbsoluteOrRelativeToCodeceptionDir(string $path): self
    {
        if (substr($path, 0, 1) === '/') {
            return new self($path);
        }
        return new self(Configuration::projectDir() . $path);
    }

    /** @throws ConfigurationException */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            throw new ConfigurationException('Could not find the configured expectations path: ' . $path);
        }
    }
}
