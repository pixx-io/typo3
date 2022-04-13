<?php

namespace Pixxio\PixxioExtension\Backend;

/*
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\ResourceStorage;
*/

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * Class InlineControlContainer
 *
 * Override core InlineControlContainer to inject Pixxio button
 */
class InlineControlContainer extends \TYPO3\CMS\Backend\Form\Container\InlineControlContainer
{

    private $applikationKey = 'ghx8F66X3ix4AJ0VmS0DE8sx7';

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
        // is necessary for Button.php
        $extensionConfiguration = \Pixxio\PixxioExtension\Utility\ConfigurationUtility::getExtensionConfiguration();

        $languageService = $this->getLanguageService();
        $buttonText = htmlspecialchars($languageService->sL('LLL:EXT:pixxio_extension/Resources/Private/Language/locallang_be.xlf:modal_view.button'));

        $foreign_table = $inlineConfiguration['foreign_table'];
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $objectPrefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;

        ob_start();
        include($_SERVER['DOCUMENT_ROOT'] . '/typo3conf/ext/pixxio_extension/Resources/Public/Partials/Button.php');
        $button = ob_get_clean();

        $this->requireJsModules[] = 'TYPO3/CMS/PixxioExtension/Script';
            

        return $button;
    }
}