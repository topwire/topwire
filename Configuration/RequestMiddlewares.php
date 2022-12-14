<?php

return [
    'frontend' => [
        'helhum/telegraph-vary-header' => [
            'target' => \Helhum\TYPO3\Telegraph\Middleware\RenderingContextResolver::class,
            'description' => '',
            'after' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
        'helhum/telegraph-rendering' => [
            'target' => \Helhum\TYPO3\Telegraph\Middleware\TelegraphRendering::class,
            'description' => '',
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
