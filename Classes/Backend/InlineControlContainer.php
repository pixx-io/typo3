<?php

namespace Pixxio\PixxioExtension\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Class InlineControlContainer
 *
 * Override core InlineControlContainer to inject Pixxio button
 */
class InlineControlContainer extends \TYPO3\CMS\Backend\Form\Container\InlineControlContainer
{
    private $applicationId = 'ghx8F66X3ix4AJ0VmS0DE8sx7';

    /**
     * @param array $inlineConfiguration
     * @return string
     */
    protected function renderPossibleRecordsSelectorTypeGroupDB(array $inlineConfiguration)
    {
        $selector = parent::renderPossibleRecordsSelectorTypeGroupDB($inlineConfiguration);

        $button = $this->renderPixxioButton($inlineConfiguration);

        // Inject button before help-block
        if (strpos($selector, '</div><div class="help-block">') > 0) {
            $selector = str_replace('</div><div class="help-block">', $button . '</div><div class="help-block">', $selector);
            // Try to inject it into the form-control container
        } elseif (preg_match('/<\/div><\/div>$/i', $selector)) {
            $selector = preg_replace('/<\/div><\/div>$/i', $button . '</div></div>', $selector);
        } else {
            $selector .= $button;
        }

        return $selector;
    }

    /**
     * @param array $inlineConfiguration
     * @return string
     */
    protected function renderPixxioButton(array $inlineConfiguration): string
    {
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
            'style' => 'margin-left:5px',
            'data-dom' => htmlspecialchars($objectPrefix),
            'data-key' => $this->applicationId,
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
            if (isset($extensionConfiguration['url'])) {
                $attributes['data-mediaspace-url'] = base64_encode($extensionConfiguration['url']);
            }
        }

        $langCode = $GLOBALS['BE_USER']->uc['lang'] ?? '';

        if ($langCode == 'default' or $langCode == '') {
            $langCode = 'en';
        }

        $iframe_url = 'https://plugin.pixx.io/static/v2/' . $langCode . '/media?multiSelect=true&applicationId=' . $this->applicationId;

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
            $iframe_url .= '&metadata=' . urlencode($field);
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
        }

        $button = '
        <span ' . GeneralUtility::implodeAttributes($attributes, true) . '>
          ' . $this->iconFactory->getIcon('actions-pixxio-extension-modal-view', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render() . $buttonText . '
        </span>
        <div class="pixxio-lightbox" style="display:none"><div class="pixxio-close"></div><div class="pixxio-lightbox-inner"><iframe class="pixxio_sdk" data-src="' . $iframe_url . '" width="100%" height="100%"></iframe></div></div>
        ';

        $this->javaScriptModules[] = JavaScriptModuleInstruction::create('@pixxio/pixxio-extension/ScriptSDK.js');

        $assetsCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $assetsCollector->addStylesheet('pixxio_extension', 'EXT:pixxio_extension/Resources/Public/StyleSheet/StyleSDK.css');

        return $button;
    }
}
