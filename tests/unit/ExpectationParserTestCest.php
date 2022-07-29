<?php

use Codeception\Exception\ParseException;
use Mcustiel\Phiremock\Codeception\Util\ExpectationAnnotationParser;

class ExpectationParserTestCest
{
    /** @var ExpectationAnnotationParser */
    private $parser;

    public function _before()
    {
        $this->parser = new ExpectationAnnotationParser(codecept_data_dir('_unique_expectations'));
    }

    public function jsonExtensionIsOptional()
    {
        $this->parser->parseExpectation("test_first_get");
        $this->parser->parseExpectation("test_first_get.json");
    }

    public function expectationNotFoundThrowsParseError(UnitTester $I)
    {
        $I->expectThrowable(ParseException::class, function () {
            $this->parser->parseExpectation("random.expectation");
        });
    }
}
