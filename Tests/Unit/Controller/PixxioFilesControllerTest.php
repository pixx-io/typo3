<?php
declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Test case
 *
 * @author pixx.io <ds@pixx.io>
 */
class PixxioFilesControllerTest extends UnitTestCase
{
    /**
     * @var \Pixxio\PixxioExtension\Controller\PixxioFilesController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(\Pixxio\PixxioExtension\Controller\PixxioFilesController::class))
            ->onlyMethods(['redirect', 'forward', 'addFlashMessage'])
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
    public function listActionFetchesAllPixxioFilesFromRepositoryAndAssignsThemToView(): void
    {
        $allPixxioFiles = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pixxioFilesRepository = $this->getMockBuilder(\::class)
            ->onlyMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $pixxioFilesRepository->expects(self::once())->method('findAll')->will(self::returnValue($allPixxioFiles));
        $this->subject->_set('pixxioFilesRepository', $pixxioFilesRepository);

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('pixxioFiles', $allPixxioFiles);
        $this->subject->_set('view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function createActionAddsTheGivenPixxioFilesToPixxioFilesRepository(): void
    {
        $pixxioFiles = new \Pixxio\PixxioExtension\Domain\Model\PixxioFiles();

        $pixxioFilesRepository = $this->getMockBuilder(\::class)
            ->onlyMethods(['add'])
            ->disableOriginalConstructor()
            ->getMock();

        $pixxioFilesRepository->expects(self::once())->method('add')->with($pixxioFiles);
        $this->subject->_set('pixxioFilesRepository', $pixxioFilesRepository);

        $this->subject->createAction($pixxioFiles);
    }

    /**
     * @test
     */
    public function editActionAssignsTheGivenPixxioFilesToView(): void
    {
        $pixxioFiles = new \Pixxio\PixxioExtension\Domain\Model\PixxioFiles();

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $this->subject->_set('view', $view);
        $view->expects(self::once())->method('assign')->with('pixxioFiles', $pixxioFiles);

        $this->subject->editAction($pixxioFiles);
    }

    /**
     * @test
     */
    public function updateActionUpdatesTheGivenPixxioFilesInPixxioFilesRepository(): void
    {
        $pixxioFiles = new \Pixxio\PixxioExtension\Domain\Model\PixxioFiles();

        $pixxioFilesRepository = $this->getMockBuilder(\::class)
            ->onlyMethods(['update'])
            ->disableOriginalConstructor()
            ->getMock();

        $pixxioFilesRepository->expects(self::once())->method('update')->with($pixxioFiles);
        $this->subject->_set('pixxioFilesRepository', $pixxioFilesRepository);

        $this->subject->updateAction($pixxioFiles);
    }

    /**
     * @test
     */
    public function deleteActionRemovesTheGivenPixxioFilesFromPixxioFilesRepository(): void
    {
        $pixxioFiles = new \Pixxio\PixxioExtension\Domain\Model\PixxioFiles();

        $pixxioFilesRepository = $this->getMockBuilder(\::class)
            ->onlyMethods(['remove'])
            ->disableOriginalConstructor()
            ->getMock();

        $pixxioFilesRepository->expects(self::once())->method('remove')->with($pixxioFiles);
        $this->subject->_set('pixxioFilesRepository', $pixxioFilesRepository);

        $this->subject->deleteAction($pixxioFiles);
    }
}
