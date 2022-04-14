<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'pixxio',
    'description' => 'Integrate pixx.io DAM Digital Asset Management into TYPO3. Use files from your pixx.io media pool with TYPO3 easily and without any detour. Use the search field to search through your pixx.io media library and find the right picture in a flash.',
    'category' => 'be',
    'author' => 'pixx.io',
    'author_email' => 'ds@pixx.io',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'version' => '0.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
