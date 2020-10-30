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

use Codeception\Exception\ConfigurationException;
use Mcustiel\Phiremock\Client\Factory;

class FactoryClass
{
    /** @var class-string<Factory> */
    private $factory;

    /**
     * @param string|class-string<Factory> $classNameConfig
     * @throws ConfigurationException
     */
    public function __construct(string $classNameConfig)
    {
        $this->factory = $this->getClassNameFromConfig($classNameConfig);
    }

    public function getInstance(): Factory
    {
        /** @var class-string<Factory> $factory */
        $factory = $this->factory;
        return $factory::createDefault();
    }

    /**
     * @return string|class-string<Factory>
     */
    public function asString(): string
    {
        return $this->factory;
    }

    /**
     * @throws ConfigurationException
     * @return string|class-string<Factory>
     */
    private function getClassNameFromConfig(string $className): string
    {
        if ($className !== 'default') {
            if (!is_a($className, Factory::class, true)) {
                throw new ConfigurationException(
                    sprintf('%s does not extend %s', $className, Factory::class)
                );
            }
            return $className;
        }
        return Factory::class;
    }
}
