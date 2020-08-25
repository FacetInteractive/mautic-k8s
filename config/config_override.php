<?php

$container->loadFromExtension('monolog', [
    'channels' => [
        'mautic',
        'chrome',
    ],
    'handlers' => [
        'main' => [
            'formatter' => 'mautic.monolog.fulltrace.formatter',
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/%kernel.environment%.php',
            'level'     => 'debug',
            'channels'  => [
                '!mautic',
            ],
            'max_files' => 7,
        ],
        'console' => [
            'type'   => 'console',
            'bubble' => false,
        ],
        'mautic' => [
            'formatter' => 'mautic.monolog.fulltrace.formatter',
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/mautic_%kernel.environment%.php',
            'level'     => 'debug',
            'channels'  => [
                'mautic',
            ],
            'max_files' => 7,
        ],
        'chrome' => [
            'type'     => 'chromephp',
            'level'    => 'debug',
            'channels' => [
                'chrome',
            ],
        ],
        'dockerlog' => [
            'type'     => 'stream',
            'path'     => 'php://stderr',
            'level'    => 'debug',
            'channels' => [
                'mautic',
            ]
        ]
    ],
]);