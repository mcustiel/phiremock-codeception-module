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

use Codeception\Exception\ParseException;
use Codeception\Test\Cest;
use Codeception\TestInterface;
use Codeception\Util\Annotation;

class ExpectationAnnotationParser
{
    /** @var string */
    private $expectationsPath;

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

    /** @throws ParseException */
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
