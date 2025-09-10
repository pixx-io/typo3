<?php
declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Controller;

use Pixxio\PixxioExtension\Controller\FilesController;
use PHPUnit\Framework\MockObject\MockObject;
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
            ->onlyMethods(['uploadPath'])
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
        // Setup
        $testDir = '/tmp/test_upload/';
        mkdir($testDir, 0755, true);
        
        $this->subject->expects(self::any())
            ->method('uploadPath')
            ->willReturn($testDir);

        $filename = 'test-image.jpg';

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals($filename, $result);
        
        // Cleanup
        rmdir($testDir);
    }

    /**
     * @test
     */
    public function generateUniqueFilenameAppendsNumberWhenFileExists(): void
    {
        // Setup
        $testDir = '/tmp/test_upload/';
        mkdir($testDir, 0777, true);
        
        $this->subject->expects(self::any())
            ->method('uploadPath')
            ->willReturn($testDir);

        $filename = 'test-image.jpg';
        
        // Create existing file to simulate conflict
        touch($testDir . $filename);

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals('test-image_1.jpg', $result);
        
        // Cleanup
        unlink($testDir . $filename);
        rmdir($testDir);
    }

    /**
     * @test
     */
    public function generateUniqueFilenameHandlesMultipleConflicts(): void
    {
        // Setup
        $testDir = '/tmp/test_upload/';
        mkdir($testDir, 0777, true);
        
        $this->subject->expects(self::any())
            ->method('uploadPath')
            ->willReturn($testDir);

        $filename = 'test-image.jpg';
        
        // Create multiple existing files to simulate conflicts
        touch($testDir . $filename);
        touch($testDir . 'test-image_1.jpg');
        touch($testDir . 'test-image_2.jpg');

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals('test-image_3.jpg', $result);
        
        // Cleanup
        unlink($testDir . $filename);
        unlink($testDir . 'test-image_1.jpg');
        unlink($testDir . 'test-image_2.jpg');
        rmdir($testDir);
    }

    /**
     * @test
     */
    public function generateUniqueFilenameHandlesFileWithoutExtension(): void
    {
        // Setup
        $testDir = '/tmp/test_upload/';
        mkdir($testDir, 0777, true);
        
        $this->subject->expects(self::any())
            ->method('uploadPath')
            ->willReturn($testDir);

        $filename = 'testfile';
        
        // Create existing file to simulate conflict
        touch($testDir . $filename);

        // Execute
        $result = $this->subject->_call('generateUniqueFilename', $filename);

        // Assert
        self::assertEquals('testfile_1', $result);
        
        // Cleanup
        unlink($testDir . $filename);
        rmdir($testDir);
    }
}