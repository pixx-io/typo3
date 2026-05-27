<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Utility;

use Pixxio\PixxioExtension\Utility\ConfigurationUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for ConfigurationUtility
 *
 * @author pixx.io <ds@pixx.io>
 */
class ConfigurationUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getCompleteUrlAddsHttpsScheme(): void
    {
        $url = 'example.pixx.io';
        $result = ConfigurationUtility::getCompleteUrl($url);

        self::assertStringStartsWith('https://', $result);
    }

    /**
     * @test
     */
    public function getCompleteUrlHandlesUrlWithPathAndAddsTrailingSlash(): void
    {
        $url = 'https://example.pixx.io/path';
        $result = ConfigurationUtility::getCompleteUrl($url);

        self::assertStringEndsWith('/', $result);
        self::assertSame('https://example.pixx.io/path/', $result);
    }

    /**
     * @test
     */
    public function getCompleteUrlHandlesUrlWithPath(): void
    {
        $url = 'example.pixx.io/path';
        $result = ConfigurationUtility::getCompleteUrl($url);

        self::assertSame('https://example.pixx.io/path/', $result);
    }

    /**
     * @test
     */
    public function getCompleteUrlHandlesUrlWithTrailingSlash(): void
    {
        $url = 'https://example.pixx.io/';
        $result = ConfigurationUtility::getCompleteUrl($url);

        self::assertSame('https://example.pixx.io/', $result);
    }

    /**
     * @test
     */
    public function getCompleteUrlHandlesEmptyString(): void
    {
        $url = '';
        $result = ConfigurationUtility::getCompleteUrl($url);

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function getCompleteUrlPreservesExistingHttpsScheme(): void
    {
        $url = 'https://example.pixx.io';
        $result = ConfigurationUtility::getCompleteUrl($url);

        self::assertStringStartsWith('https://', $result);
    }

    /**
     * @test
     */
    public function getCompleteUrlHandlesUrlWithoutPath(): void
    {
        $url = 'example.pixx.io';
        $result = ConfigurationUtility::getCompleteUrl($url);

        // URL without path should not get trailing slash (only domain)
        self::assertSame('https://example.pixx.io', $result);
    }

    /**
     * @test
     */
    public function getCompleteUrlHandlesComplexUrl(): void
    {
        $url = 'subdomain.example.pixx.io/api/v2';
        $result = ConfigurationUtility::getCompleteUrl($url);

        self::assertSame('https://subdomain.example.pixx.io/api/v2/', $result);
    }
}
