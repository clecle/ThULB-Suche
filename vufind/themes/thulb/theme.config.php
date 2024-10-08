<?php
return array(
    'extends' => 'bootstrap3',
    'helpers' => array(
        'factories' => array(
            \ThULB\View\Helper\Record\OnlineContent::class => \ThULB\View\Helper\Record\OnlineContentFactory::class,
            \ThULB\View\Helper\Record\SeraHelper::class => \ThULB\View\Helper\Record\SeraHelperFactory::class,
            \ThULB\View\Helper\Root\AccountMenu::class => \VuFind\View\Helper\Root\AccountMenuFactory::class,
            \ThULB\View\Helper\Root\AvailabilityStatus::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            \ThULB\View\Helper\Root\Citation::class => \VuFind\View\Helper\Root\CitationFactory::class,
            \ThULB\View\Helper\Root\Flashmessages::class => \VuFind\View\Helper\Root\FlashmessagesFactory::class,
            \ThULB\View\Helper\Root\LocationData::class => \ThULB\View\Helper\Root\LocationDataFactory::class,
            \ThULB\View\Helper\Root\NormLink::class => \ThULB\View\Helper\Root\NormlinkFactory::class,
            \ThULB\View\Helper\Root\Record::class => \ThULB\View\Helper\Root\RecordFactory::class,
            \ThULB\View\Helper\Root\RecordLinker::class => \VuFind\View\Helper\Root\RecordLinkerFactory::class,
            \ThULB\View\Helper\Root\ServerType::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            \ThULB\View\Helper\Root\ServiceDesk::class => \ThULB\View\Helper\Root\ServiceDeskFactory::class,
            \ThULB\View\Helper\Root\Session::class => \VuFind\View\Helper\Root\SessionFactory::class,
            \ThULB\View\Helper\Root\UserType::class => \ThULB\View\Helper\Root\InvokableFactoryWithSessionManager::class,
            \VuFind\View\Helper\Root\RecordDataFormatter::class => \ThULB\View\Helper\Root\RecordDataFormatterFactory::class,
        ),
        'aliases' => array(
            'accountMenu' => \ThULB\View\Helper\Root\AccountMenu::class,
            'availabilityStatus' => \ThULB\View\Helper\Root\AvailabilityStatus::class,
            'citation' => \ThULB\View\Helper\Root\Citation::class,
            'flashmessages' => \ThULB\View\Helper\Root\Flashmessages::class,
            'record' => \ThULB\View\Helper\Root\Record::class,
            'recordLinker' => \ThULB\View\Helper\Root\RecordLinker::class,
            'thulb_locationData' => \ThULB\View\Helper\Root\LocationData::class,
            'thulb_normlink' => \ThULB\View\Helper\Root\NormLink::class,
            'thulb_onlineContent' => \ThULB\View\Helper\Record\OnlineContent::class,
            'thulb_serverType' => \ThULB\View\Helper\Root\ServerType::class,
            'thulb_session' => \ThULB\View\Helper\Root\Session::class,
            'thulb_sera' => \ThULB\View\Helper\Record\SeraHelper::class,
            'thulb_serviceDesk' => \ThULB\View\Helper\Root\ServiceDesk::class,
            'thulb_userType' => \ThULB\View\Helper\Root\UserType::class,
        ),
    ),
    'favicon' => 'thulb_favicon.png',
    'icons' => array(
        'aliases' => array(
            'cart-add' => 'FontAwesome:star',
            'cart-remove' => 'FontAwesome:star-o',
            'export' => 'FontAwesome:share',
            'facet-checked' => 'FontAwesome:check',
            'id-card' => 'FontAwesome:id-card-o',
            'lock' => 'FontAwesome:lock',
            'status-available' => 'FontAwesome:check',
            'status-unavailable' => 'FontAwesome:remove',
            'status-unknown' => 'FontAwesome:circle',
            'ui-delete' => 'FontAwesome:trash',
        )
    ),
    'js' => array(
        'thulb.js',
        'jquery-ui.min.js',
    ),
);
