<?php
return array(
    'extends' => 'thulb',
    'favicon' => 'logo_favicon.png',
    'helpers' => array (
        'factories' => array (
            \DHGE\View\Helper\Root\DoiLinker::class => 'DHGE\View\Helper\Root\Factory::getDoiLinker',
            \DHGE\View\Helper\Root\Session::class => 'DHGE\View\Helper\Root\Factory::getSession',
        ),
        'aliases' => array (
            'dhge_doiLinker' => \DHGE\View\Helper\Root\DoiLinker::class,
            'dhge_session' => \DHGE\View\Helper\Root\Session::class,
        ),
    ),
);
