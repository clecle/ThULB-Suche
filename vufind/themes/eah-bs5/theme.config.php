<?php
return array(
    'extends' => 'thulb-bs5',
    'favicon' => 'favicon.ico',
    'helpers' => array(
        'factories' => array(
            \EAH\View\Helper\Record\OnlineContent::class => \ThULB\View\Helper\Record\OnlineContentFactory::class,
        ),
        'aliases' => array(
            \ThULB\View\Helper\Record\OnlineContent::class => \EAH\View\Helper\Record\OnlineContent::class,
        ),
    ),
    'icons' => array(
        'aliases' => array(
            'cart' => 'FontAwesome:star',
            'my-account' => 'Image:Bibliothekskonto.svg',
            'sign-in' => 'Image:Bibliothekskonto.svg',
        )
    ),
);
