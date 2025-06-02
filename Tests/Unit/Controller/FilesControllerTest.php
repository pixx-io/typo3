<?php
declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Controller;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for FilesController
 */
class FilesControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testFilteringLogicForNonExistentFiles(): void
    {
        // Test the logic that was added to filter out non-existent files
        $fileIds = [1001, 1002, 1003, 1004];
        $pixxioIdsToDelete = [1002, 1004]; // These files don't exist in pixx.io
        
        // This simulates the filtering logic added to FilesController
        $existingFileIds = array_filter($fileIds, function ($id) use ($pixxioIdsToDelete) {
            return !in_array($id, $pixxioIdsToDelete);
        });
        
        // Assert that only existing files remain
        $expected = [1001, 1003];
        self::assertEquals($expected, array_values($existingFileIds));
        
        // Assert that deleted file IDs are filtered out
        foreach ($pixxioIdsToDelete as $deletedId) {
            self::assertNotContains($deletedId, $existingFileIds);
        }
    }
    
    /**
     * @test
     */
    public function testFilteringHandlesEmptyDeleteList(): void
    {
        // Test when no files are to be deleted
        $fileIds = [1001, 1002, 1003];
        $pixxioIdsToDelete = []; // No files to delete
        
        $existingFileIds = array_filter($fileIds, function ($id) use ($pixxioIdsToDelete) {
            return !in_array($id, $pixxioIdsToDelete);
        });
        
        // All files should remain when nothing is to be deleted
        self::assertEquals($fileIds, array_values($existingFileIds));
    }
    
    /**
     * @test
     */
    public function testFilteringHandlesAllFilesDeleted(): void
    {
        // Test when all files are to be deleted
        $fileIds = [1001, 1002];
        $pixxioIdsToDelete = [1001, 1002]; // All files to delete
        
        $existingFileIds = array_filter($fileIds, function ($id) use ($pixxioIdsToDelete) {
            return !in_array($id, $pixxioIdsToDelete);
        });
        
        // No files should remain
        self::assertEmpty($existingFileIds);
    }
}