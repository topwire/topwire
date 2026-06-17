<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Turbo for TYPO3',
    'description' => 'Turbo for TYPO3',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'Helmut Hummel',
    'author_email' => 'typo3@helhum.io',
    'author_company' => '',
    'version' => '1.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.5-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
