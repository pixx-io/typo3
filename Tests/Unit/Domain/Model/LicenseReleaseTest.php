<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Domain\Model;

use Pixxio\PixxioExtension\Domain\Model\LicenseRelease;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for LicenseRelease Model
 *
 * @author pixx.io <ds@pixx.io>
 */
class LicenseReleaseTest extends UnitTestCase
{
    protected LicenseRelease $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new LicenseRelease();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function getPixxioIdReturnsInitialValueForString(): void
    {
        self::assertSame('', $this->subject->getPixxioId());
    }

    #[Test]
    public function setPixxioIdForStringSetsPixxioId(): void
    {
        $pixxioId = 'test-pixxio-id-123';
        $this->subject->setPixxioId($pixxioId);

        self::assertSame($pixxioId, $this->subject->getPixxioId());
    }

    #[Test]
    public function getLicenseProviderReturnsInitialValueForString(): void
    {
        self::assertSame('', $this->subject->getLicenseProvider());
    }

    #[Test]
    public function setLicenseProviderForStringSetsLicenseProvider(): void
    {
        $provider = 'Getty Images';
        $this->subject->setLicenseProvider($provider);

        self::assertSame($provider, $this->subject->getLicenseProvider());
    }

    #[Test]
    public function getNameReturnsInitialValueForString(): void
    {
        self::assertSame('', $this->subject->getName());
    }

    #[Test]
    public function setNameForStringSetsName(): void
    {
        $name = 'Test License Name';
        $this->subject->setName($name);

        self::assertSame($name, $this->subject->getName());
    }

    #[Test]
    public function isShowWarningMessageReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->isShowWarningMessage());
    }

    #[Test]
    public function setShowWarningMessageForBoolSetsShowWarningMessage(): void
    {
        $this->subject->setShowWarningMessage(true);

        self::assertTrue($this->subject->isShowWarningMessage());
    }

    #[Test]
    public function getWarningMessageReturnsInitialValueForString(): void
    {
        self::assertSame('', $this->subject->getWarningMessage());
    }

    #[Test]
    public function setWarningMessageForStringSetsWarningMessage(): void
    {
        $message = 'This license will expire soon';
        $this->subject->setWarningMessage($message);

        self::assertSame($message, $this->subject->getWarningMessage());
    }

    #[Test]
    public function getExpiresReturnsInitialValueForString(): void
    {
        self::assertSame('', $this->subject->getExpires());
    }

    #[Test]
    public function setExpiresForStringSetsExpires(): void
    {
        $expires = '2026-12-31';
        $this->subject->setExpires($expires);

        self::assertSame($expires, $this->subject->getExpires());
    }
}
