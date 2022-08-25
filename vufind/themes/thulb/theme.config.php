<?php
return array(
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            \ThULB\View\Helper\Root\Citation::class => \VuFind\View\Helper\Root\CitationFactory::class,
            \ThULB\View\Helper\Root\DoiLinker::class => 'ThULB\View\Helper\Root\Factory::getDoiLinker',
            \ThULB\View\Helper\Root\Flashmessages::class => \VuFind\View\Helper\Root\FlashmessagesFactory::class,
            'ThULB\View\Helper\Root\Record' => 'ThULB\View\Helper\Root\Factory::getRecord',
            'ThULB\View\Helper\Root\RecordLinker' => 'ThULB\View\Helper\Root\Factory::getRecordLinker',
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'ThULB\View\Helper\Root\RecordDataFormatterFactory',
            'ThULB\View\Helper\Root\ServerType' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'ThULB\View\Helper\Root\Session' => 'ThULB\View\Helper\Root\Factory::getSession',
            \ThULB\View\Helper\Record\OnlineContent::class => \ThULB\View\Helper\Record\OnlineContentFactory::class,
            \ThULB\View\Helper\Record\SeraHelper::class => \ThULB\View\Helper\Record\SeraHelperFactory::class,
        ],
        'aliases' => array (
            'citation' => \ThULB\View\Helper\Root\Citation::class,
            'flashmessages' => 'ThULB\View\Helper\Root\Flashmessages',
            'record' => 'ThULB\View\Helper\Root\Record',
            'recordLinker' => 'ThULB\View\Helper\Root\RecordLinker',
            'thulb_serverType' => 'ThULB\View\Helper\Root\ServerType',
            'thulb_session' => 'ThULB\View\Helper\Root\Session',
            'thulb_doiLinker' => \ThULB\View\Helper\Root\DoiLinker::class,
            'thulb_onlineContent' => \ThULB\View\Helper\Record\OnlineContent::class,
            'thulb_sera' => \ThULB\View\Helper\Record\SeraHelper::class
        ),
    ],
    'favicon' => 'thulb_favicon.png',
    'js' => array(
        'thulb.js',
        'jquery-ui.min.js',
    ),
);
