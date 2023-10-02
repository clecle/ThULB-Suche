<?php
return array(
    'extends' => 'bootstrap3',
    'helpers' => array(
        'factories' => array(
            \ThULB\View\Helper\Record\OnlineContent::class => \ThULB\View\Helper\Record\OnlineContentFactory::class,
            \ThULB\View\Helper\Record\SeraHelper::class => \ThULB\View\Helper\Record\SeraHelperFactory::class,
            \ThULB\View\Helper\Root\Citation::class => \VuFind\View\Helper\Root\CitationFactory::class,
            \ThULB\View\Helper\Root\Flashmessages::class => \VuFind\View\Helper\Root\FlashmessagesFactory::class,
            \ThULB\View\Helper\Root\Record::class => \ThULB\View\Helper\Root\RecordFactory::class,
            \ThULB\View\Helper\Root\RecordLinker::class => \VuFind\View\Helper\Root\RecordLinkerFactory::class,
            \ThULB\View\Helper\Root\ServerType::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            \ThULB\View\Helper\Root\ServiceDesk::class => \ThULB\View\Helper\Root\ServiceDeskFactory::class,
            \ThULB\View\Helper\Root\Session::class => \VuFind\View\Helper\Root\SessionFactory::class,
            \VuFind\View\Helper\Root\RecordDataFormatter::class => \ThULB\View\Helper\Root\RecordDataFormatterFactory::class,
        ),
        'aliases' => array(
            'citation' => \ThULB\View\Helper\Root\Citation::class,
            'flashmessages' => \ThULB\View\Helper\Root\Flashmessages::class,
            'record' => \ThULB\View\Helper\Root\Record::class,
            'recordLinker' => \ThULB\View\Helper\Root\RecordLinker::class,
            'thulb_onlineContent' => \ThULB\View\Helper\Record\OnlineContent::class,
            'thulb_serverType' => \ThULB\View\Helper\Root\ServerType::class,
            'thulb_session' => \ThULB\View\Helper\Root\Session::class,
            'thulb_sera' => \ThULB\View\Helper\Record\SeraHelper::class,
            'thulb_serviceDesk' => \ThULB\View\Helper\Root\ServiceDesk::class,
        ),
    ),
    'favicon' => 'thulb_favicon.png',
    'icons' => array(
        'aliases' => array(
            'status-available' => 'FontAwesome:check',
            'status-unavailable' => 'FontAwesome:remove',
            'status-unknown' => 'FontAwesome:circle',
        )
    ),
    'js' => array(
        'thulb.js',
        'jquery-ui.min.js',
    ),
);
