<?php
return array(
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'ThULB\View\Helper\Root\RecordDataFormatterFactory',
            'ThULB\View\Helper\Root\RecordLink' => 'ThULB\View\Helper\Root\Factory::getRecordLink',
            'ThULB\View\Helper\Root\Record' => 'ThULB\View\Helper\Root\Factory::getRecord',
            'ThULB\View\Helper\Root\ServerType' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'ThULB\View\Helper\Root\Session' => 'ThULB\View\Helper\Root\Factory::getSession',
        ],
        'aliases' => array (
            'record' => 'ThULB\View\Helper\Root\Record',
            'recordlink' => 'ThULB\View\Helper\Root\RecordLink',
            'thulb_serverType' => 'ThULB\View\Helper\Root\ServerType',
            'thulb_session' => 'ThULB\View\Helper\Root\Session',
        ),
    ],
    'favicon' => 'thulb_favicon.ico',
    'js' => array(
        'thulb.js',
        'jquery-ui.min.js',
    ),
);
