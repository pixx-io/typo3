<?php

declare(strict_types=1);

use TYPO3\CMS\Reactions\Form\Element\FieldMapElement;
use TYPO3\CMS\Backend\Form\Container\FilesControlContainer;

defined('TYPO3') or die();

if(!defined('TYPO3_PIXXIO_EXT_NUM')) {
    define('TYPO3_PIXXIO_EXT_NUM', 1554937800);
}

//$GLOBALS['TYPO3_CONF_VARS']['BE']['HTTP']['Response']['Headers']['csp'] = "default-src 'unsafe-inline' 'unsafe-eval'; script-src 'unsafe-inline' 'unsafe-eval' https://machwert.pixxio.media";

/*
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1660911089] = [
    'nodeName' => 'inputText',
    'priority' => 90,
    'class' => \Pixxio\PixxioExtension\Form\Element\InputTextElement::class,
];
*/

    // adds pixx.io button by overwriting TYPO3 InlineController
    
    /*
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1554937800] = [
        'nodeName' => 'inline',
        'priority' => 92,
        'class' => \Pixxio\PixxioExtension\Backend\InlineControlContainer::class,
    ];
    */

    /*
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1554937800] = [
        'nodeName' => 'inline',
        'priority' => 92,
        'class' => \Pixxio\PixxioExtension\Controller\FileListController::class,
    ];
    */

    /*
    // Register a node in ext_localconf.php
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361297] = [
        'nodeName' => 'customMapElement',
        'priority' => 40,
        'class' => \Pixxio\PixxioExtension\Form\Element\CustomMapElement::class,
    ];
    */

    /*
    $typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
    if ($typo3Version->getMajorVersion() < 12) {
        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
            'TYPO3/CMS/MyExtension/MyJavaScript'
        )->instance($fieldId);
    } else {
        $resultArray['javaScriptModules'][] =
            JavaScriptModuleInstruction::create('@myvendor/my_extension/my-javascript.js');
    }
    */



    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1554937800] = [
        'nodeName' => FilesControlContainer::NODE_TYPE_IDENTIFIER,
        'priority' => 92,
        'class' => \Pixxio\PixxioExtension\Controller\FilesControlContainer::class,
    ];



    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['de']['EXT:pixxio_extension/Resources/Private/Language/locallang_be.xlf'][] = 'EXT:pixxio_extension/Resources/Private/Language/Overrides/de.locallang_be.xlf';
    
    
    /**
     * Add Icons for BE Module
     */
    
    if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adminpanel')) {
        $icons = [
            'actions-pixxio-extension-modal-view' => 'Extension.svg'
        ];
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        foreach ($icons as $key => $file) {
            if (!$iconRegistry->isRegistered($key)) {
                $iconRegistry->registerIcon(
                    $key,
                    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                    ['source' => 'EXT:pixxio_extension/Resources/Public/Icons/' . $file]
                );
            }
        }
    }

