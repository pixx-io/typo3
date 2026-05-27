<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Condition;

use Pixxio\PixxioExtension\Condition\ShowCropIfNotPixxioDirectLink;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Doctrine\DBAL\Result;

/**
 * Test case for ShowCropIfNotPixxioDirectLink Condition
 *
 * @author pixx.io <ds@pixx.io>
 */
class ShowCropIfNotPixxioDirectLinkTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected ShowCropIfNotPixxioDirectLink $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ShowCropIfNotPixxioDirectLink();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function evaluateReturnsTrueWhenFileUidIsZero(): void
    {
        $record = ['uid_local' => 0];

        $result = $this->subject->evaluate($record);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateReturnsTrueWhenUidLocalIsMissing(): void
    {
        $record = [];

        $result = $this->subject->evaluate($record);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateReturnsTrueWhenPixxioDirectLinkIsNotSet(): void
    {
        $fileUid = 123;
        $record = ['uid_local' => $fileUid];

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAssociative')->willReturn([]);

        $queryBuilderMock = $this->createQueryBuilderMock($fileUid, $resultMock);
        $this->mockConnectionPool($queryBuilderMock);

        $result = $this->subject->evaluate($record);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateReturnsTrueWhenPixxioDirectLinkIsFalse(): void
    {
        $fileUid = 456;
        $record = ['uid_local' => $fileUid];

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAssociative')->willReturn(['pixxio_is_direct_link' => 0]);

        $queryBuilderMock = $this->createQueryBuilderMock($fileUid, $resultMock);
        $this->mockConnectionPool($queryBuilderMock);

        $result = $this->subject->evaluate($record);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateReturnsFalseWhenPixxioDirectLinkIsTrue(): void
    {
        $fileUid = 789;
        $record = ['uid_local' => $fileUid];

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAssociative')->willReturn(['pixxio_is_direct_link' => 1]);

        $queryBuilderMock = $this->createQueryBuilderMock($fileUid, $resultMock);
        $this->mockConnectionPool($queryBuilderMock);

        $result = $this->subject->evaluate($record);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function evaluateHandlesNestedRecordStructure(): void
    {
        $fileUid = 111;
        $record = [
            'record' => [
                'uid_local' => $fileUid
            ]
        ];

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAssociative')->willReturn([]);

        $queryBuilderMock = $this->createQueryBuilderMock($fileUid, $resultMock);
        $this->mockConnectionPool($queryBuilderMock);

        $result = $this->subject->evaluate($record);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateHandlesArrayUidLocal(): void
    {
        $fileUid = 222;
        $record = [
            'uid_local' => [
                ['uid' => $fileUid]
            ]
        ];

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAssociative')->willReturn([]);

        $queryBuilderMock = $this->createQueryBuilderMock($fileUid, $resultMock);
        $this->mockConnectionPool($queryBuilderMock);

        $result = $this->subject->evaluate($record);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateHandlesArrayUidLocalWithDirectUid(): void
    {
        $fileUid = 333;
        $record = [
            'uid_local' => [$fileUid]
        ];

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAssociative')->willReturn([]);

        $queryBuilderMock = $this->createQueryBuilderMock($fileUid, $resultMock);
        $this->mockConnectionPool($queryBuilderMock);

        $result = $this->subject->evaluate($record);

        self::assertTrue($result);
    }

    private function createQueryBuilderMock(int $expectedFileUid, Result $resultMock): QueryBuilder
    {
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $expressionBuilderMock->method('eq')->willReturn('file = ' . $expectedFileUid);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('select')->willReturnSelf();
        $queryBuilderMock->method('from')->willReturnSelf();
        $queryBuilderMock->method('where')->willReturnSelf();
        $queryBuilderMock->method('expr')->willReturn($expressionBuilderMock);
        $queryBuilderMock->method('createNamedParameter')->willReturn(':file');
        $queryBuilderMock->method('executeQuery')->willReturn($resultMock);

        return $queryBuilderMock;
    }

    private function mockConnectionPool(QueryBuilder $queryBuilderMock): void
    {
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getQueryBuilderForTable')
            ->with('sys_file_metadata')
            ->willReturn($queryBuilderMock);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
    }
}
