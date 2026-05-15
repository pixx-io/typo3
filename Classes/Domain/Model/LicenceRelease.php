<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class LicenseRelease extends AbstractEntity
{
    protected string $licenseProvider = '';
    protected string $name = '';
    protected bool $showWarningMessage = false;
    protected string $warningMessage = '';
    protected string $expires = '';

    public function getLicenseProvider(): string
    {
        return $this->licenseProvider;
    }

    public function setLicenseProvider(string $licenseProvider): void
    {
        $this->licenseProvider = $licenseProvider;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isShowWarningMessage(): bool
    {
        return $this->showWarningMessage;
    }

    public function setShowWarningMessage(bool $showWarningMessage): void
    {
        $this->showWarningMessage = $showWarningMessage;
    }

    public function getWarningMessage(): string
    {
        return $this->warningMessage;
    }

    public function setWarningMessage(string $warningMessage): void
    {
        $this->warningMessage = $warningMessage;
    }

    public function getExpires(): string
    {
        return $this->expires;
    }

    public function setExpires(string $expires): void
    {
        $this->expires = $expires;
    }
}
