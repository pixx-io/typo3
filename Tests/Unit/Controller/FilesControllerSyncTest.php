<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Controller;

use Pixxio\PixxioExtension\Controller\FilesController;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * FilesControllerSyncTest
 *
 * Test for sync functionality in FilesController
 */
class FilesControllerSyncTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /** @var FilesController&MockObject&AccessibleObjectInterface */
    protected FilesController $subject;

    /** @var SymfonyStyle&MockObject */
    protected SymfonyStyle $ioMock;

    /** @var QueryBuilder&MockObject */
    protected QueryBuilder $queryBuilderMock;

    /** @var ConnectionPool&MockObject */
    protected ConnectionPool $connectionPoolMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock SymfonyStyle for console output
        $this->ioMock = $this->createMock(SymfonyStyle::class);

        // Setup QueryBuilder mock
        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->setupQueryBuilderMock();

        // Mock ConnectionPool
        $this->connectionPoolMock = $this->createMock(ConnectionPool::class);
        $this->connectionPoolMock->method('getQueryBuilderForTable')
            ->willReturn($this->queryBuilderMock);

        GeneralUtility::addInstance(ConnectionPool::class, $this->connectionPoolMock);

        // Create accessible mock of FilesController
        $this->subject = $this->getAccessibleMock(
            FilesController::class,
            null,
            [],
            '',
            false
        );

        // Mock the extensionConfiguration property
        $this->subject->_set('extensionConfiguration', [
            'delete' => true,
            'update' => true,
            'update_metadata' => true,
        ]);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    protected function setupQueryBuilderMock(): void
    {
        $restrictionsMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $restrictionsMock->method('add')->willReturnSelf();

        $connectionMock = $this->createMock(Connection::class);

        $this->queryBuilderMock->method('getRestrictions')
            ->willReturn($restrictionsMock);
        $this->queryBuilderMock->method('select')
            ->willReturnSelf();
        $this->queryBuilderMock->method('from')
            ->willReturnSelf();
        $this->queryBuilderMock->method('where')
            ->willReturnSelf();
        $this->queryBuilderMock->method('leftJoin')
            ->willReturnSelf();
        $this->queryBuilderMock->method('getConnection')
            ->willReturn($connectionMock);
        $this->queryBuilderMock->method('expr')
            ->willReturn(new \TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder(
                $connectionMock
            ));
        $this->queryBuilderMock->method('createNamedParameter')
            ->willReturnCallback(fn($value) => (string)$value);
        $this->queryBuilderMock->method('quoteIdentifier')
            ->willReturnCallback(fn($value) => $value);
    }

    #[Test]
    public function syncSingleFileActionReturnsErrorWhenNoIdProvided(): void
    {
        $this->ioMock->expects(self::once())
            ->method('error')
            ->with('Either TYPO3 file UID or pixx.io ID must be provided');

        $result = $this->subject->syncSingleFileAction($this->ioMock, null, null);

        self::assertFalse($result);
    }

    #[Test]
    public function syncSingleFileActionReturnsErrorWhenFileNotFound(): void
    {
        $this->ioMock->expects(self::once())
            ->method('error')
            ->with('No file found with the provided ID');

        // Mock query to return no results
        $statementMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $statementMock->method('fetchAllAssociative')->willReturn([]);
        $this->queryBuilderMock->method('executeQuery')->willReturn($statementMock);

        $result = $this->subject->syncSingleFileAction($this->ioMock, '123', null);

        self::assertFalse($result);
    }

    #[Test]
    public function syncSingleFileActionReturnsErrorWhenFileHasNoPixxioId(): void
    {
        $fileWithoutPixxioId = [
            'file' => 123,
            'identifier' => '/path/to/file.jpg',
            'pixxio_file_id' => 0,
        ];

        // Mock query to return file without pixxio_file_id
        $statementMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $statementMock->method('fetchAllAssociative')->willReturn([$fileWithoutPixxioId]);
        $this->queryBuilderMock->method('executeQuery')->willReturn($statementMock);

        $this->ioMock->expects(self::once())
            ->method('error')
            ->with(self::stringContains('do not have a pixx.io ID'));

        $result = $this->subject->syncSingleFileAction($this->ioMock, '123', null);

        self::assertFalse($result);
    }
}
