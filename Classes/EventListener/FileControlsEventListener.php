<?php

namespace Pixxio\PixxioExtension\EventListener;

use Pixxio\PixxioExtension\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Form\Event\CustomFileControlsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FileControlsEventListener
{
    private $applicationId = 'ghx8F66X3ix4AJ0VmS0DE8sx7';

    public function __construct(
        protected IconFactory $iconFactory
    ) {}

    #[AsEventListener]
    public function __invoke(CustomFileControlsEvent $event)
    {
        if ($this->shouldAddButton($event)) {
            $this->addButton($event);
        }
    }

    protected function shouldAddButton(CustomFileControlsEvent $event): bool
    {
        $config = $event->getFieldConfig();

        $fileExtensionFilter = new FileExtensionFilter();
        $fileExtensionFilter->setAllowedFileExtensions($config['allowed'] ?? '');
        $onlineMediaAllowed = [];
        foreach (GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)->getSupportedFileExtensions() as $supportedFileExtension) {
            if ($fileExtensionFilter->isAllowed($supportedFileExtension)) {
                $onlineMediaAllowed[] = $supportedFileExtension;
            }
        }

        $showUpload = (bool)($config['appearance']['fileUploadAllowed'] ?? true);
        $showByUrl = ($config['appearance']['fileByUrlAllowed'] ?? true) && $onlineMediaAllowed !== [];

        $backendUser = $this->getBackendUserAuthentication();
        $pixxioUploadAllowed = (isset($backendUser->uc['show_pixxioUpload']) &&  $backendUser->uc['show_pixxioUpload'] === '0') ? false : true;

        return ($showUpload || $showByUrl) && $pixxioUploadAllowed;
    }

    protected function addButton(CustomFileControlsEvent $event)
    {
        $resultArray = $event->getResultArray();

        $extensionConfiguration = ConfigurationUtility::getExtensionConfiguration();
        $languageService = $this->getLanguageService();
        $buttonText = htmlspecialchars($languageService->sL('LLL:EXT:pixxio_extension/Resources/Private/Language/locallang_be.xlf:modal_view.button'));
        $foreignTable = $event->getFieldConfig()['foreign_table'];
        $objectPrefix = $event->getFormFieldIdentifier() . '-' . $foreignTable;
        $attributes = [
            'type' => 'button',
            'class' => 'btn btn-default pixxio pixxio-sdk-btn',
            'title' => $buttonText,
            'style' => 'margin-left:5px',
            'data-dom' => htmlspecialchars($objectPrefix),
            'data-key' => $this->applicationId,
            'data-url' => $extensionConfiguration['url'],
            'data-token' => $extensionConfiguration['token_refresh'],
            'data-uid' => uniqid(),
        ];

        // Add auto-login data attributes if enabled
        if (isset($extensionConfiguration['auto_login']) && $extensionConfiguration['auto_login']) {
            $attributes['data-auto-login'] = '1';
            if (isset($extensionConfiguration['token_refresh'])) {
                $attributes['data-refresh-token'] = base64_encode($extensionConfiguration['token_refresh']);
            }
            if (isset($extensionConfiguration['url'])) {
                $attributes['data-mediaspace-url'] = base64_encode($extensionConfiguration['url']);
            }
        }

        // @todo Should be implemented as web component
        $event->addControl(
            '
            <button ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                ' . $this->iconFactory->getIcon('tx-pixxio-extension-icon', IconSize::SMALL)->render() . '
                ' . htmlspecialchars($buttonText) . '
            </button>'
        );

        $iframeLanguage = $languageService->getLocale();
        $iframeUrl = 'https://plugin.pixx.io/static/v2/' . $iframeLanguage . '/media?multiSelect=true&applicationId=' . $this->applicationId;

        // Load additional metadata to be independent from the sync job
        $metadataFields = [
            'City',
            'Country',
            'Region',
            'CopyrightNotice',
            'Model',
            'Source',
            'ColorSpace',
            'Publisher',
            'location',
            'createDate',
            'modifyDate',
            'creator',
            'rating'
        ];

        foreach ($metadataFields as $field) {
            $iframeUrl .= '&metadata=' . urlencode($field);
        }

        if (isset($extensionConfiguration['alt_text'])) {
            $iframeUrl .= '&metadata=' . urlencode($extensionConfiguration['alt_text']);
        }

        // Add allowedDownloadFormats parameter if configured
        if (isset($extensionConfiguration['allowed_download_formats']) && !empty($extensionConfiguration['allowed_download_formats'])) {
            $allowedFormats = $extensionConfiguration['allowed_download_formats'];
            
            // Handle comma-separated values
            if (strpos($allowedFormats, ',') !== false) {
                $formats = array_map('trim', explode(',', $allowedFormats));
                foreach ($formats as $format) {
                    if (!empty($format)) {
                        $iframeUrl .= '&allowedDownloadFormats=' . urlencode($format);
                    }
                }
            } else {
                // Single value
                $iframeUrl .= '&allowedDownloadFormats=' . urlencode($allowedFormats);
            }
        }

        $event->addControl(
            '<div class="pixxio-lightbox"><div class="pixxio-close"></div><div class="pixxio-lightbox-inner"><iframe class="pixxio_sdk" data-src="' . $iframeUrl . '" width="100%" height="100%"></iframe></div></div>'
        );

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@pixxio/pixxio-extension/ScriptSDK.js');
        $resultArray['stylesheetFiles']['pixxio_extension'] = 'EXT:pixxio_extension/Resources/Public/StyleSheet/StyleSDK.css';
        $event->setResultArray($resultArray);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
