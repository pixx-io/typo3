<?php
defined('TYPO3') || die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['f'][] = 'Pixxio\\PixxioExtension\\ViewHelpers';

// Load pixx.io JavaScript module and CSS globally in the backend
// This ensures it's available even for dynamically loaded IRRE elements
if (\TYPO3\CMS\Core\Core\Environment::isCli() === false) {
    $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    $pageRenderer->loadJavaScriptModule('@pixxio/pixxio-extension/ScriptSDK.js');
    $pageRenderer->addCssFile('EXT:pixxio_extension/Resources/Public/StyleSheet/StyleSDK.css');
}
