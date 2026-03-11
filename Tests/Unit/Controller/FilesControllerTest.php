<?php
declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Controller;

use Pixxio\PixxioExtension\Controller\FilesController;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for FilesController
 *
 * @author pixx.io <ds@pixx.io>
 */
class FilesControllerTest extends UnitTestCase
{
    /**
     * @var FilesController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(FilesController::class))
            ->onlyMethods(['uploadFolder'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function generateUniqueFilenameReturnsOriginalWhenNoConflict(): void
    {
        // Setup - Mock Folder object that reports no file exists
        $folderMock = $this->createMock(Folder::class);
        $folderMock->expects(self::once())
            ->method('hasFile')
            ->with('test-image.jpg')
            ->willReturn(false);

        $this->subject->expects(self::any())
            ->method('uploadFolder')
            ->willReturn($folderMock);

        $filename = 'test-image.jpg';

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals($filename, $result);
    }

    /**
     * @test
     */
    public function generateUniqueFilenameAppendsNumberWhenFileExists(): void
    {
        // Setup - Mock Folder object that reports first file exists, second doesn't
        $folderMock = $this->createMock(Folder::class);
        $folderMock->expects(self::exactly(2))
            ->method('hasFile')
            ->willReturnCallback(function ($filename) {
                // Original file exists, numbered version doesn't
                return $filename === 'test-image.jpg';
            });

        $this->subject->expects(self::any())
            ->method('uploadFolder')
            ->willReturn($folderMock);

        $filename = 'test-image.jpg';

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals('test-image_1.jpg', $result);
    }

    /**
     * @test
     */
    public function generateUniqueFilenameHandlesMultipleConflicts(): void
    {
        // Setup - Mock Folder object that reports multiple files exist
        $folderMock = $this->createMock(Folder::class);
        $folderMock->expects(self::exactly(4))
            ->method('hasFile')
            ->willReturnCallback(function ($filename) {
                // Original and numbered versions _1, _2 exist; _3 doesn't
                $existingFiles = ['test-image.jpg', 'test-image_1.jpg', 'test-image_2.jpg'];
                return in_array($filename, $existingFiles, true);
            });

        $this->subject->expects(self::any())
            ->method('uploadFolder')
            ->willReturn($folderMock);

        $filename = 'test-image.jpg';

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals('test-image_3.jpg', $result);
    }

    /**
     * @test
     */
    public function generateUniqueFilenameHandlesFileWithoutExtension(): void
    {
        // Setup - Mock Folder object that reports file exists
        $folderMock = $this->createMock(Folder::class);
        $folderMock->expects(self::exactly(2))
            ->method('hasFile')
            ->willReturnCallback(function ($filename) {
                // Original file exists, numbered version doesn't
                return $filename === 'testfile';
            });

        $this->subject->expects(self::any())
            ->method('uploadFolder')
            ->willReturn($folderMock);

        $filename = 'testfile';

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals('testfile_1', $result);
    }
}
