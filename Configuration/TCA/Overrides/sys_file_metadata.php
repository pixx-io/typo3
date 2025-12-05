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
    ),
    'pixxio_is_direct_link' => array (
      'exclude' => 0,
      'label' => 'pixx.io Is a direct link',
      'config' => array (
        'readOnly' => true,
        'type' => 'check',
      )
    ),
    'pixxio_direct_link' => array (
      'exclude' => 0,
      'label' => 'pixx.io Direct link',
      'config' => array (
        'type' => 'input',
      )
    ),
    'tx_pixxioextension_licensereleases' => [
        'label' => 'pixx.io License Releases',
        'config' => [
            'readOnly' => true,
            'type' => 'inline',
            'foreign_table' => 'tx_pixxioextension_domain_model_licenserelease',
            #'MM' => 'tx_pixxioextension_sys_file_metadata_licenserelease_mm', // MM-Tabelle (siehe unten)
            'maxitems' => 9999,
            'appearance' => [
                'useSortable' => true,
                'showPossibleLocalizationRecords' => true,
                'showRemovedLocalizationRecords' => true,
                'showAllLocalizationLink' => true,
                'showSynchronizationLink' => true,
            ],
        ],
    ],
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
  'pixxio_file_id, pixxio_file_id_removed',
  '',
  'after:title'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
  'sys_file_metadata',
  'pixxio_downloadformat',
  '',
  'after:title'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
  'sys_file_metadata',
  'pixxio_is_direct_link',
  '',
  'after:title'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
  'sys_file_metadata',
  'pixxio_direct_link',
  '',
  'after:title'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    'tx_pixxioextension_licensereleases',
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
  'sys_file_reference',
  'imageoverlayPalette',
  'pixxio_downloadformat'
);

