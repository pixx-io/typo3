<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Command;

use Pixxio\PixxioExtension\Command\SyncCommand;
use Pixxio\PixxioExtension\Controller\FilesController;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * SyncCommandTest
 *
 * Test for sync command
 */
class SyncCommandTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected SyncCommand $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SyncCommand();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function commandHasCorrectConfiguration(): void
    {
        $definition = $this->subject->getDefinition();
        
        self::assertTrue($definition->hasOption('fid'));
        self::assertTrue($definition->hasOption('pid'));
    }

    #[Test]
    public function executeSyncsAllFilesWhenNoOptionsProvided(): void
    {
        $filesControllerMock = $this->createMock(FilesController::class);
        $filesControllerMock->expects(self::once())
            ->method('syncAction')
            ->willReturn(true);

        GeneralUtility::addInstance(FilesController::class, $filesControllerMock);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')
            ->willReturnMap([
                ['fid', null],
                ['pid', null],
            ]);

        $outputMock = $this->createMock(OutputInterface::class);

        $result = $this->subject->run($inputMock, $outputMock);

        self::assertEquals(Command::SUCCESS, $result);
    }

    #[Test]
    public function executeSyncsSingleFileWithFidOption(): void
    {
        $filesControllerMock = $this->createMock(FilesController::class);
        $filesControllerMock->expects(self::once())
            ->method('syncSingleFileAction')
            ->with(
                self::anything(),
                '123',
                null
            )
            ->willReturn(true);

        GeneralUtility::addInstance(FilesController::class, $filesControllerMock);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')
            ->willReturnMap([
                ['fid', '123'],
                ['pid', null],
            ]);

        $outputMock = $this->createMock(OutputInterface::class);

        $result = $this->subject->run($inputMock, $outputMock);

        self::assertEquals(Command::SUCCESS, $result);
    }

    #[Test]
    public function executeSyncsSingleFileWithPidOption(): void
    {
        $filesControllerMock = $this->createMock(FilesController::class);
        $filesControllerMock->expects(self::once())
            ->method('syncSingleFileAction')
            ->with(
                self::anything(),
                null,
                '456789'
            )
            ->willReturn(true);

        GeneralUtility::addInstance(FilesController::class, $filesControllerMock);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')
            ->willReturnMap([
                ['fid', null],
                ['pid', '456789'],
            ]);

        $outputMock = $this->createMock(OutputInterface::class);

        $result = $this->subject->run($inputMock, $outputMock);

        self::assertEquals(Command::SUCCESS, $result);
    }

    #[Test]
    public function executeReturnsInvalidWhenBothOptionsProvided(): void
    {
        $filesControllerMock = $this->createMock(FilesController::class);
        $filesControllerMock->expects(self::never())
            ->method('syncAction');
        $filesControllerMock->expects(self::never())
            ->method('syncSingleFileAction');

        GeneralUtility::addInstance(FilesController::class, $filesControllerMock);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')
            ->willReturnMap([
                ['fid', '123'],
                ['pid', '456789'],
            ]);

        $outputMock = $this->createMock(OutputInterface::class);

        $result = $this->subject->run($inputMock, $outputMock);

        self::assertEquals(Command::INVALID, $result);
    }

    #[Test]
    public function executeReturnsFailureWhenSyncFails(): void
    {
        $filesControllerMock = $this->createMock(FilesController::class);
        $filesControllerMock->expects(self::once())
            ->method('syncAction')
            ->willReturn(false);

        GeneralUtility::addInstance(FilesController::class, $filesControllerMock);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')
            ->willReturnMap([
                ['fid', null],
                ['pid', null],
            ]);

        $outputMock = $this->createMock(OutputInterface::class);

        $result = $this->subject->run($inputMock, $outputMock);

        self::assertEquals(Command::FAILURE, $result);
    }

    #[Test]
    public function executeReturnsFailureOnRuntimeException(): void
    {
        $filesControllerMock = $this->createMock(FilesController::class);
        $filesControllerMock->expects(self::once())
            ->method('syncAction')
            ->willThrowException(new \RuntimeException('Test exception'));

        GeneralUtility::addInstance(FilesController::class, $filesControllerMock);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')
            ->willReturnMap([
                ['fid', null],
                ['pid', null],
            ]);

        $outputMock = $this->createMock(OutputInterface::class);

        $result = $this->subject->run($inputMock, $outputMock);

        self::assertEquals(Command::FAILURE, $result);
    }
}
