<?php
defined('TYPO3') or die();

call_user_func(function()
{
    $extensionKey = 'pixxio_extension';

    /**
     * Default TypoScript
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript',
        'pixx.io configuration to use with fluid styled content'
    );
});
