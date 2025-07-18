<?php
return [
    'ctrl' => [
        'label' => 'name',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY name',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:pixxio_extension/Resources/Public/Icons/tx_pixxioextension_domain_model_pixxiofiles.gif'
    ],
    'interface' => [
        'showRecordFieldList' => implode(',', [
            'hidden',
            'license_provider',
            'name',
            'show_warning_message',
            'warning_message',
            'expires',
            'images',
        ]),
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'Hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled', ''],
                ]
            ],
        ],
        'license_provider' => [
            'exclude' => true,
            'label' => 'License Provider',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255',
                'eval' => 'trim',
                'readOnly' => true,
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'Name',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255',
                'eval' => 'trim',
                'readOnly' => true,
            ],
        ],
        'show_warning_message' => [
            'exclude' => true,
            'label' => 'Show Warning Message',
            'config' => [
                'type' => 'check',
                'readOnly' => true,
            ],
        ],
        'warning_message' => [
            'exclude' => true,
            'label' => 'Warning Message',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '15',
                'readOnly' => true,
            ],
        ],
        'expires' => [
            'exclude' => true,
            'label' => 'Expires',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255',
                'eval' => 'trim',
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => implode(',', [
                'hidden',
                'license_provider',
                'name',
                'show_warning_message',
                'warning_message',
                'expires',
            ]),
        ],
    ],
];
