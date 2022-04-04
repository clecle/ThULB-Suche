<?php
return array(
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'ThULB\View\Helper\Root\RecordDataFormatterFactory',
            \ThULB\View\Helper\Root\Flashmessages::class => \VuFind\View\Helper\Root\FlashmessagesFactory::class,
            'ThULB\View\Helper\Root\RecordLinker' => 'ThULB\View\Helper\Root\Factory::getRecordLinker',
            'ThULB\View\Helper\Root\Record' => 'ThULB\View\Helper\Root\Factory::getRecord',
            'ThULB\View\Helper\Root\ServerType' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'ThULB\View\Helper\Root\Session' => 'ThULB\View\Helper\Root\Factory::getSession',
            \ThULB\View\Helper\Root\DoiLinker::class => 'ThULB\View\Helper\Root\Factory::getDoiLinker',
        ],
        'aliases' => array (
            'flashmessages' => 'ThULB\View\Helper\Root\Flashmessages',
            'record' => 'ThULB\View\Helper\Root\Record',
            'recordLinker' => 'ThULB\View\Helper\Root\RecordLinker',
            'thulb_serverType' => 'ThULB\View\Helper\Root\ServerType',
            'thulb_session' => 'ThULB\View\Helper\Root\Session',
            'thulb_doiLinker' => \ThULB\View\Helper\Root\DoiLinker::class,
        ),
    ],
    'favicon' => 'thulb_favicon.png',
    'js' => array(
        'thulb.js',
        'jquery-ui.min.js',
    ),
);
