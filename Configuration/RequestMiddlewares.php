<?php

return [
    'frontend' => [
        'helhum/topwire-vary-header' => [
            'target' => \Helhum\Topwire\Middleware\TopwireContextResolver::class,
            'description' => '',
            'after' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
        'helhum/topwire-rendering' => [
            'target' => \Helhum\Topwire\Middleware\TopwireRendering::class,
            'description' => '',
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
