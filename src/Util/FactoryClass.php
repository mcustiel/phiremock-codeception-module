<?php

namespace Mcustiel\Phiremock\Codeception\Util;

use Codeception\Exception\ConfigurationException;
use Mcustiel\Phiremock\Client\Factory;

class FactoryClass
{
    /** @var string */
    private $factory;

    public function __construct(string $classNameConfig)
    {
        $this->factory = $this->getClassNameFromConfig($classNameConfig);
    }

    public function getInstance(): Factory
    {
        $factory = $this->factory;
        return $factory::createDefault();
    }

    public function asString(): string
    {
        return $this->factory;
    }

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
