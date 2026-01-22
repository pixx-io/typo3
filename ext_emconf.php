<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'pixx.io',
    'description' => 'Integrate pixx.io DAM Digital Asset Management into TYPO3. Use files from your pixx.io media pool with TYPO3 easily and without any detour. Use the search field to search through your pixx.io media library and find the right picture in a flash.',
    'category' => 'be',
    'author' => 'pixx.io',
    'author_email' => 'plugin@pixx.io',
    'state' => 'stable',
    'version' => '2.4.1',
    'constraints' => [
        'depends' => [
            'fluid_styled_content' => '12.4.0-12.99.99',
            'typo3' => '10.4.0-12.99.99',
        ],
    ],
];
