<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Unit\Event;

use Pixxio\PixxioExtension\Event\MetaDataAfterPopulateEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for MetaDataAfterPopulateEvent
 */
class MetaDataAfterPopulateEventTest extends UnitTestCase
{
    #[Test]
    public function getAdditionalFieldsReturnsFieldsPassedToConstructor(): void
    {
        $additionalFields = [
            'title' => 'A pixx.io file',
            'description' => 'Some description',
        ];
        $pixxioFile = (object)['id' => 42];

        $event = new MetaDataAfterPopulateEvent($additionalFields, $pixxioFile);

        self::assertSame($additionalFields, $event->getAdditionalFields());
    }

    #[Test]
    public function getFileReturnsFileObjectPassedToConstructor(): void
    {
        $pixxioFile = (object)['id' => 42, 'subject' => 'A pixx.io file'];

        $event = new MetaDataAfterPopulateEvent([], $pixxioFile);

        self::assertSame($pixxioFile, $event->getFile());
    }

    #[Test]
    public function setAdditionalFieldsOverridesFieldsReturnedByGetAdditionalFields(): void
    {
        $pixxioFile = (object)['id' => 42];
        $event = new MetaDataAfterPopulateEvent(['title' => 'Original title'], $pixxioFile);

        $modifiedFields = ['title' => 'Title changed by an event listener'];
        $event->setAdditionalFields($modifiedFields);

        self::assertSame($modifiedFields, $event->getAdditionalFields());
    }

    #[Test]
    public function setAdditionalFieldsAllowsAddingNewFields(): void
    {
        $pixxioFile = (object)['id' => 42];
        $event = new MetaDataAfterPopulateEvent(['title' => 'Original title'], $pixxioFile);

        $event->setAdditionalFields([
            'title' => 'Original title',
            'custom_field' => 'Added by an event listener',
        ]);

        self::assertSame(
            [
                'title' => 'Original title',
                'custom_field' => 'Added by an event listener',
            ],
            $event->getAdditionalFields()
        );
    }
}
