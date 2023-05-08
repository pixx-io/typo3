<?php
defined('TYPO3_MODE') || die('Access denied.');

if (!defined('TYPO3_PIXXIO_EXT_NUM')) define('TYPO3_PIXXIO_EXT_NUM', 1554937800);

// adds pixx.io button by overwriting TYPO3 InlineController
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1554937800] = [
    'nodeName' => 'inline',
    'priority' => 55,
    'class' => \Pixxio\PixxioExtension\Backend\InlineControlContainer::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['de']['EXT:pixxio_extension/Resources/Private/Language/locallang_be.xlf'][] = 'EXT:pixxio_extension/Resources/Private/Language/Overrides/de.locallang_be.xlf';

// register pixxi.io icon
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'actions-pixxio-extension-modal-view',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:pixxio_extension/Resources/Public/Icons/Extension.svg']
);
unset($iconRegistry);
