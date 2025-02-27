<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'pixx.io',
    'description' => 'Integrate pixx.io DAM Digital Asset Management into TYPO3. Use files from your pixx.io media pool with TYPO3 easily and without any detour. Use the search field to search through your pixx.io media library and find the right picture in a flash.',
    'category' => 'be',
    'author' => 'pixx.io',
    'author_email' => 'ds@pixx.io',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '2.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
    ],
];
