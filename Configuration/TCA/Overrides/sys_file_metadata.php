<?php
defined('TYPO3') || die();
 
/***************
 * Add to the image a checkbox - Responsive Image
 */
$temporaryPixxioFields = array(
    'pixxio_mediaspace' => array (
      'exclude' => 0,
      'label' => 'pixx.io Mediaspace',
      'config' => array (
        'readOnly' => true,
        'type' => 'input',
      )
    ),
    'pixxio_file_id' => array (
      'exclude' => 0,
      'label' => 'pixx.io File ID',
      'config' => array (
        'readOnly' => true,
        'type' => 'input',
      )
    ),
    'pixxio_downloadformat' => array (
      'exclude' => 0,
      'label' => 'pixx.io Download Format',
      'config' => array (
        'readOnly' => true,
        'type' => 'input',
      )
    )
);
 
// add field to tca
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
  'sys_file_metadata',
  $temporaryPixxioFields
);
 
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
  'sys_file_metadata',
  'pixxio_mediaspace',
  '',
  'after:title'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
  'sys_file_metadata',
  'pixxio_file_id',
  '',
  'after:title'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
  'sys_file_metadata',
  'pixxio_downloadformat',
  '',
  'after:title'
);

 
// add new field image_responsive in Image Overlay
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
  'sys_file_reference',
  'imageoverlayPalette',
  'pixxio_mediaspace'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
  'sys_file_reference',
  'imageoverlayPalette',
  'pixxio_file_id'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
  'sys_file_reference',
  'imageoverlayPalette',
  'pixxio_downloadformat'
);

