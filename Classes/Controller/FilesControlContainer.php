<?php

declare(strict_types=1);

/*
 * This file overwrites the method "getFileSelectors" of FilesControlContainer class from TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Pixxio\PixxioExtension\Controller;

use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\DefaultUploadFolderResolver;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Page\AssetCollector;
/**
 * Files entry container.
 *
 * This container is the entry step to rendering a file reference. It is created by SingleFieldContainer.
 *
 * The code creates the main structure for the single file reference, initializes the inlineData array,
 * that is manipulated and also returned back in its manipulated state. The "control" stuff of file
 * references is rendered here, for example the "create new" button.
 *
 * For each existing file reference, a FileReferenceContainer is called for further processing.
 */
class FilesControlContainer extends \TYPO3\CMS\Backend\Form\Container\FilesControlContainer
{
    private $applicationId = 'ghx8F66X3ix4AJ0VmS0DE8sx7';

    private const FILE_REFERENCE_TABLE = 'sys_file_reference';

    /**
     * Inline data array used in JS, returned as JSON object to frontend
     */
    protected array $fileReferenceData = [];

    /**
     * @var array<int,JavaScriptModuleInstruction|string|array<string,string>>
     */
    protected array $javaScriptModules = [];

    protected IconFactory $iconFactory;
    protected InlineStackProcessor $inlineStackProcessor;

    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
    ];

    /**
     * Generate buttons to select, reference and upload files.
     */
    protected function getFileSelectors(array $inlineConfiguration, FileExtensionFilter $fileExtensionFilter): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();

        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . self::FILE_REFERENCE_TABLE;

        $controls = [];
        if ($inlineConfiguration['appearance']['elementBrowserEnabled'] ?? true) {
            if ($inlineConfiguration['appearance']['createNewRelationLinkTitle'] ?? false) {
                $buttonText = $inlineConfiguration['appearance']['createNewRelationLinkTitle'];
            } else {
                $buttonText = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.createNewRelation';
            }
            $buttonText = $languageService->sL($buttonText);
            $attributes = [
                'type' => 'button',
                'class' => 'btn btn-default t3js-element-browser',
                'style' => !($inlineConfiguration['inline']['showCreateNewRelationButton'] ?? true) ? 'display: none;' : '',
                'title' => $buttonText,
                'data-mode' => 'file',
                'data-params' => '|||allowed=' . implode(',', $fileExtensionFilter->getAllowedFileExtensions() ?? []) . ';disallowed=' . implode(',', $fileExtensionFilter->getDisallowedFileExtensions() ?? []) . '|' . $objectPrefix,
            ];
            $controls[] = '
                <button ' . GeneralUtility::implodeAttributes($attributes, true) . '>
				    ' . $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL)->render() . '
				    ' . htmlspecialchars($buttonText) . '
			    </button>';
        }

        $onlineMediaAllowed = [];
        foreach (GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)->getSupportedFileExtensions() as $supportedFileExtension) {
            if ($fileExtensionFilter->isAllowed($supportedFileExtension)) {
                $onlineMediaAllowed[] = $supportedFileExtension;
            }
        }

        $showUpload = (bool)($inlineConfiguration['appearance']['fileUploadAllowed'] ?? true);
        $showByUrl = ($inlineConfiguration['appearance']['fileByUrlAllowed'] ?? true) && $onlineMediaAllowed !== [];
        $pixxioUploadAllowed = (isset($backendUser->uc['show_pixxioUpload']) &&  $backendUser->uc['show_pixxioUpload'] === '0') ? false : true;

        if (($showUpload || $showByUrl) && $pixxioUploadAllowed) {
            $defaultUploadFolderResolver = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);
            $folder = $defaultUploadFolderResolver->resolve(
                $backendUser,
                $this->data['tableName'] === 'pages' ? $this->data['vanillaUid'] : ($this->data['parentPageRow']['uid'] ?? 0),
                $this->data['tableName'],
                $this->data['fieldName']
            );
            if (
                $folder instanceof Folder
                && $folder->getStorage()->checkUserActionPermission('add', 'File')
            ) {
                if ($showUpload) {
                    if ($inlineConfiguration['appearance']['uploadFilesLinkTitle'] ?? false) {
                        $buttonText = $inlineConfiguration['appearance']['uploadFilesLinkTitle'];
                    } else {
                        $buttonText = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.select-and-submit';
                    }
                    $buttonText = $languageService->sL($buttonText);

                    $attributes = [
                        'type' => 'button',
                        'class' => 'btn btn-default t3js-drag-uploader',
                        'title' => $buttonText,
                        'style' => !($inlineConfiguration['inline']['showCreateNewRelationButton'] ?? true) ? 'display: none;' : '',
                        'data-dropzone-target' => '#' . StringUtility::escapeCssSelector($currentStructureDomObjectIdPrefix),
                        'data-insert-dropzone-before' => '1',
                        'data-file-irre-object' => $objectPrefix,
                        'data-file-allowed' => implode(',', $fileExtensionFilter->getAllowedFileExtensions() ?? []),
                        'data-file-disallowed' => implode(',', $fileExtensionFilter->getDisallowedFileExtensions() ?? []),
                        'data-target-folder' => $folder->getCombinedIdentifier(),
                        'data-max-file-size' => (string)(GeneralUtility::getMaxUploadFileSize() * 1024),
                    ];
                    $controls[] = '
                        <button ' . GeneralUtility::implodeAttributes($attributes, true) . '>
					        ' . $this->iconFactory->getIcon('actions-upload', Icon::SIZE_SMALL)->render() . '
                            ' . htmlspecialchars($buttonText) . '
                        </button>';

                    $this->javaScriptModules[] = JavaScriptModuleInstruction::create('@typo3/backend/drag-uploader.js');
                }
                if ($showByUrl) {
                    if ($inlineConfiguration['appearance']['addMediaLinkTitle'] ?? false) {
                        $buttonText = $inlineConfiguration['appearance']['addMediaLinkTitle'];
                    } else {
                        $buttonText = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.button';
                    }
                    $buttonText = $languageService->sL($buttonText);
                    $attributes = [
                        'type' => 'button',
                        'class' => 'btn btn-default t3js-online-media-add-btn',
                        'title' => $buttonText,
                        'style' => !($inlineConfiguration['inline']['showOnlineMediaAddButtonStyle'] ?? true) ? 'display: none;' : '',
                        'data-target-folder' => $folder->getCombinedIdentifier(),
                        'data-file-irre-object' => $objectPrefix,
                        'data-online-media-allowed' => implode(',', $onlineMediaAllowed),
                        'data-btn-submit' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.placeholder'),
                        'data-placeholder' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.placeholder'),
                        'data-online-media-allowed-help-text' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.allowEmbedSources'),
                    ];

                    // @todo Should be implemented as web component
                    $controls[] = '
                        <button ' . GeneralUtility::implodeAttributes($attributes, true) . '>
							' . $this->iconFactory->getIcon('actions-online-media-add', Icon::SIZE_SMALL)->render() . '
							' . htmlspecialchars($buttonText) . '
                        </button>';

                    $this->javaScriptModules[] = JavaScriptModuleInstruction::create('@typo3/backend/online-media.js');
                }
            }

            $extensionConfiguration = \Pixxio\PixxioExtension\Utility\ConfigurationUtility::getExtensionConfiguration();

            $languageService = $this->getLanguageService();
            $buttonText = htmlspecialchars($languageService->sL('LLL:EXT:pixxio_extension/Resources/Private/Language/locallang_be.xlf:modal_view.button'));

            $foreign_table = $inlineConfiguration['foreign_table'];
            $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
            $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;

            $attributes = [
                'type' => 'button',
                'class' => 'btn btn-default pixxio pixxio-sdk-btn',
                'title' => $buttonText,
                'style' => 'margin-left:5px;' . (!($inlineConfiguration['inline']['showCreateNewRelationButton'] ?? true) ? 'display: none;' : ''),
                'data-dom' => htmlspecialchars($objectPrefix),
                'data-key'=> $this->applicationId,
                'data-url' => $extensionConfiguration['url'],
                'data-token' => $extensionConfiguration['token_refresh'],
                'data-uid' => uniqid()
            ];

            // Add auto-login data attributes if enabled
            if (isset($extensionConfiguration['auto_login']) && $extensionConfiguration['auto_login']) {
                $attributes['data-auto-login'] = '1';
                if (isset($extensionConfiguration['token_refresh'])) {
                    $attributes['data-refresh-token'] = base64_encode($extensionConfiguration['token_refresh']);
                }
                if (isset($extensionConfiguration['user_id'])) {
                    $attributes['data-user-id'] = base64_encode($extensionConfiguration['user_id']);
                }
                if (isset($extensionConfiguration['url'])) {
                    $attributes['data-mediaspace-url'] = base64_encode($extensionConfiguration['url']);
                }
            }

            // @todo Should be implemented as web component
            $controls[] = '
                <button ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                    ' . $this->iconFactory->getIcon('tx-pixxio-extension-icon', Icon::SIZE_SMALL)->render() . '
                    ' . htmlspecialchars($buttonText) . '
                </button>';

            $iframe_lang = $languageService->getLocale();
            $iframe_url = 'https://plugin.pixx.io/static/v1/' . $iframe_lang . '/media?multiSelect=true&applicationId='.$this->applicationId;

            if (isset($extensionConfiguration['use_cdn_links']) && $extensionConfiguration['use_cdn_links'] == true) {
                $iframe_url .= '&useDirectLinks=true';
            }

            if (isset($extensionConfiguration['alt_text'])) {
                $iframe_url .= '&metadata=' . urlencode($extensionConfiguration['alt_text']);
            }
            // Add allowedDownloadFormats parameter if configured
            if (isset($extensionConfiguration['allowed_download_formats']) && !empty($extensionConfiguration['allowed_download_formats'])) {
                $allowedFormats = $extensionConfiguration['allowed_download_formats'];

                // Handle comma-separated values
                if (strpos($allowedFormats, ',') !== false) {
                    $formats = array_map('trim', explode(',', $allowedFormats));
                    foreach ($formats as $format) {
                        if (!empty($format)) {
                            $iframe_url .= '&allowedDownloadFormats=' . urlencode($format);
                        }
                    }
                } else {
                    // Single value
                    $iframe_url .= '&allowedDownloadFormats=' . urlencode($allowedFormats);
                }
            } else {
                $iframe_url .= '&allowedDownloadFormats=original&allowedDownloadFormats=preview';
            }

            $tldPos = strpos($extensionConfiguration['url'],'//');
            if ($tldPos > 0) {
                $pixxioMediaspace = substr($extensionConfiguration['url'],$tldPos+2);
            } else {
                $pixxioMediaspace = $extensionConfiguration['url'];
            }
            $controls[] = '
            <div class="pixxio-lightbox"><div class="pixxio-close"></div><div class="pixxio-lightbox-inner"><iframe class="pixxio_sdk" data-src="'.$iframe_url .'" width="100%" height="100%"></iframe></div></div>
            ';

            $this->javaScriptModules[] = JavaScriptModuleInstruction::create('@pixxio/pixxio-extension/ScriptSDK.js');
            $assetsCollector = GeneralUtility::makeInstance(AssetCollector::class);
            $assetsCollector->addStylesheet('pixxio_extension','EXT:pixxio_extension/Resources/Public/StyleSheet/StyleSDK.css');
        }

        return $controls;
    }
}
