<?php
namespace Pixxio\PixxioExtension\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class LicenceRelease extends AbstractEntity {

    /**
     * @var string
     **/
    protected $licenseProvider = '';

    /**
     * @var string
     **/
    protected $name = '';

    /**
     * @var bool
     **/
    protected $showWarningMessage = false;

    /**
     * @var string
     **/
    protected $warningMessage = '';

    /**
     * @var string
     **/
    protected $expires = '';

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
