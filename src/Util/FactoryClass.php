<?php

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
