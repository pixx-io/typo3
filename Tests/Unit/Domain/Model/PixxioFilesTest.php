<?php
declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Domain\Model;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 *
 * @author pixx.io <ds@pixx.io>
 */
class PixxioFilesTest extends UnitTestCase
{
    /**
     * @var \Pixxio\PixxioExtension\Domain\Model\PixxioFiles|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMock(
            \Pixxio\PixxioExtension\Domain\Model\PixxioFiles::class,
            ['dummy']
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getPixxioFileIdReturnsInitialValueForInt(): void
    {
        self::assertSame(
            0,
            $this->subject->getPixxioFileId()
        );
    }

    /**
     * @test
     */
    public function setPixxioFileIdForIntSetsPixxioFileId(): void
    {
        $this->subject->setPixxioFileId(12);

        self::assertEquals(12, $this->subject->_get('pixxioFileId'));
    }

    /**
     * @test
     */
    public function getPixxioMediaspaceReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getPixxioMediaspace()
        );
    }

    /**
     * @test
     */
    public function setPixxioMediaspaceForStringSetsPixxioMediaspace(): void
    {
        $this->subject->setPixxioMediaspace('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->_get('pixxioMediaspace'));
    }

    /**
     * @test
     */
    public function getPixxioDownloadformatReturnsInitialValueForInt(): void
    {
        self::assertSame(
            0,
            $this->subject->getPixxioDownloadformat()
        );
    }

    /**
     * @test
     */
    public function setPixxioDownloadformatForIntSetsPixxioDownloadformat(): void
    {
        $this->subject->setPixxioDownloadformat(12);

        self::assertEquals(12, $this->subject->_get('pixxioDownloadformat'));
    }
}
