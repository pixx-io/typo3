<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Event;

/**
 * Dispatched after the extension has assembled the metadata fields for a
 * pixx.io file (e.g. title, description, alternative text) but before they
 * are written to the sys_file_metadata record.
 *
 * Event listeners can inspect the pixx.io file object and adjust, add or
 * remove fields via setAdditionalFields()/getAdditionalFields() to influence
 * what is finally persisted.
 */
final class MetaDataAfterPopulateEvent
{
    /**
     * @param array<mixed> $additionalFields
     * @param object $file
     */
    public function __construct(
        private array $additionalFields,
        private readonly object $file
    ) {
    }

    public function getFile(): object
    {
        return $this->file;
    }

    /**
     * @return array<mixed>
     */
    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }

    /**
     * @param array<mixed> $additionalFields
     * @return void
     */
    public function setAdditionalFields(array $additionalFields): void
    {
        $this->additionalFields = $additionalFields;
    }
}
