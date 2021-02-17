<?php

use Codeception\Exception\ConfigurationException;
use Mcustiel\Phiremock\Codeception\Util\DirectoryPath;

class DirectoryPathCest
{
    public function testWindowsFullPath(UnitTester $I): void
    {
        try {
            DirectoryPath::createAbsoluteOrRelativeToCodeceptionDir('C:\\PotatoPhiremock');
        } catch (ConfigurationException $e) {
            $I->assertSame(
                'Could not find the configured expectations path: C:\\PotatoPhiremock',
                $e->getMessage()
            );
        }
    }

    public function testLinuxFullPath(UnitTester $I): void
    {
        try {
            DirectoryPath::createAbsoluteOrRelativeToCodeceptionDir('/home/phiremock/potato');
        } catch (ConfigurationException $e) {
            $I->assertSame(
                'Could not find the configured expectations path: /home/phiremock/potato',
                $e->getMessage()
            );
        }
    }
}
