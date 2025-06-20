<?php

namespace Pixxio\PixxioExtension\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\AssetCollector;

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

        $langCode = $GLOBALS['BE_USER']->uc['lang'] ?? '';

        if ($langCode == 'default' OR $langCode == '') {
            $langCode = 'en';
        }

        $iframe_url = 'https://plugin.pixx.io/static/v1/' .$langCode. '/media?multiSelect=true&applicationId='.$this->applicationId;

        $tldPos = strpos($extensionConfiguration['url'],'//');
        if (isset($extensionConfiguration['url'])) {
            if ($tldPos > 0) {
                $pixxioMediaspace = substr($extensionConfiguration['url'],$tldPos+2);
            } else {
                $pixxioMediaspace = $extensionConfiguration['url'];
            }
        }

        if (isset($extensionConfiguration['alt_text'])) {
            $iframe_url .= '&metadata=' . urlencode($extensionConfiguration['alt_text']);
        }

        $tokenRefresh = '';
        $userId = '';

        if(isset($extensionConfiguration['token_refresh'])) {
            $tokenRefresh = base64_encode($extensionConfiguration['token_refresh']);
        }
        if(isset($extensionConfiguration['user_id'])) {
            $userId = base64_encode($extensionConfiguration['user_id']);
        }

        $button = '
        <span ' . GeneralUtility::implodeAttributes($attributes, true) . '>
          '.$this->iconFactory->getIcon('actions-pixxio-extension-modal-view', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render().$buttonText.'
        </span>
        <div class="pixxio-lightbox" style="display:none"><div class="pixxio-close"></div><div class="pixxio-lightbox-inner"><iframe class="pixxio_sdk" data-src="'.$iframe_url .'" width="100%" height="100%"></iframe></div></div>
        ';

        $this->requireJsModules[] = 'TYPO3/CMS/PixxioExtension/ScriptSDK_v11';

        $assetsCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $assetsCollector->addStylesheet('pixxio_extension','EXT:pixxio_extension/Resources/Public/StyleSheet/StyleSDK.css');

        return $button;
    }
}