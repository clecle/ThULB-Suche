<?php
return array(
    'extends' => 'thulb',
    'favicon' => 'logo_favicon.png',
    'helpers' => array (
        'factories' => array (
            \DHGE\View\Helper\Root\Session::class => 'DHGE\View\Helper\Root\Factory::getSession'
        ),
        'aliases' => array (
            'dhge_session' => DHGE\View\Helper\Root\Session::class,
        ),
    ),
);
