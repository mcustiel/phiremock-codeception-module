<?php

namespace Mcustiel\Phiremock\Codeception\Util;

use Codeception\Exception\ParseException;
use Codeception\Test\Cest;
use Codeception\TestInterface;
use Codeception\Util\Annotation;

class ExpectationAnnotationParser
{
    /** @var array */
    private $expectationsPath;

    /**
     * ExpectationAnnotationParser constructor.
     *
     * @throws \Codeception\Exception\ConfigurationException
     */
    public function __construct(string $expectationsPath)
    {
        $this->expectationsPath = $expectationsPath;
    }

    /**
     * @param TestInterface|Cest $test
     *
     * @return array
     */
    public function getExpectations(TestInterface $test): array
    {
        if (!$test instanceof Cest) {
            return [];
        }
        $expectations = Annotation::forMethod($test->getTestClass(), $test->getTestMethod())->fetchAll('expectation');

        return array_map([$this, 'parseExpectation'], $expectations);
    }

    /**
     * @throws ParseException
     * @return string
     */
    public function parseExpectation(string $expectationAnnotation): string
    {
        $matches = [];
        $expectationRegex = '/\(?\"?(?<filePath>[a-zA-Z0-9_\\/]+)(.json)?\"?\)?/';
        preg_match($expectationRegex, $expectationAnnotation, $matches);

        if (empty($matches)) {
            throw new ParseException("The 'expectation' annotation could not be parsed (found: '$expectationAnnotation')");
        }

        $expectationPath = $this->getExpectationFullPath("{$matches['filePath']}.json");
        if (!file_exists($expectationPath)) {
            throw new ParseException("The expectation at $expectationPath could not be found ");
        }

        return $expectationPath;
    }

    private function getExpectationFullPath($path): string
    {
        return sprintf('%s/%s', $this->expectationsPath, $path);
    }
}
