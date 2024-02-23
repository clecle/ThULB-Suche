<?php
return array(
    'extends' => 'thulb',
    'helpers' => array(
        'factories' => array(
            \EAH\View\Helper\Record\OnlineContent::class => \ThULB\View\Helper\Record\OnlineContentFactory::class,
        ),
        'aliases' => array(
            \ThULB\View\Helper\Record\OnlineContent::class => \EAH\View\Helper\Record\OnlineContent::class,
        ),
    ),
    'favicon' => 'favicon.ico'
);
