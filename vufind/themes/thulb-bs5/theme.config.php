<?php
return array(
    'extends' => 'bootstrap5',
    'favicon' => 'thulb_favicon.svg',
    'helpers' => array(
        'factories' => array(
            \ThULB\View\Helper\Record\OnlineContent::class => \ThULB\View\Helper\Record\OnlineContentFactory::class,
            \ThULB\View\Helper\Record\SeraHelper::class => \ThULB\View\Helper\Record\SeraHelperFactory::class,
            \ThULB\View\Helper\Root\AccountMenu::class => \VuFind\View\Helper\Root\AccountMenuFactory::class,
            \ThULB\View\Helper\Root\AvailabilityStatus::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            \ThULB\View\Helper\Root\Citation::class => \VuFind\View\Helper\Root\CitationFactory::class,
            \ThULB\View\Helper\Root\CookieConsent::class => \VuFind\View\Helper\Root\CookieConsentFactory::class,
            \ThULB\View\Helper\Root\LocationData::class => \ThULB\View\Helper\Root\LocationDataFactory::class,
            \ThULB\View\Helper\Root\NormLink::class => \ThULB\View\Helper\Root\NormlinkFactory::class,
            \ThULB\View\Helper\Root\Record::class => \ThULB\View\Helper\Root\RecordFactory::class,
            \ThULB\View\Helper\Root\RecordLinker::class => \VuFind\View\Helper\Root\RecordLinkerFactory::class,
            \ThULB\View\Helper\Root\SearchTabs::class => \VuFind\View\Helper\Root\SearchTabsFactory::class,
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
            'cookieConsent' => \ThULB\View\Helper\Root\CookieConsent::class,
            'record' => \ThULB\View\Helper\Root\Record::class,
            'recordLinker' => \ThULB\View\Helper\Root\RecordLinker::class,
            'searchTabs' => \ThULB\View\Helper\Root\SearchTabs::class,
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
    'icons' => array(
        'sets' => [
            /**
             * Define icon sets here.
             *
             * All sets need:
             * - 'template': which template the icon renders with
             * - 'src': the location of the relevant resource (font, css, images)
             * - 'prefix': prefix to place before each icon name for convenience
             *             (ie. fa fa- for FontAwesome, default "")
             */
            'Image' => [
                'template' => 'images',
                'prefix' => '',
                'src' => '.',
            ],
        ],
        'aliases' => array(
            'broken-link' => 'FontAwesome:unlink',
            'cart' => 'FontAwesome:star-o',
            'cart-add' => 'FontAwesome:star-o',
            'cart-remove' => 'FontAwesome:star',
            'cite' => 'FontAwesome:quote-left',
            'export' => 'FontAwesome:share',
            'external-link' => 'FontAwesome:arrow-up-right-from-square',
            'facet-checked' => 'FontAwesome:check',
            'id-card' => 'FontAwesome:id-card-o',
            'ill' => 'FontAwesome:book',
            'information' => 'FontAwesome:info',
            'lock' => 'FontAwesome:lock',
            'my-account' => 'Image:user_account_white.svg',
            'page-next' => 'FontAwesome:angles-right',
            'page-prev' => 'FontAwesome:angles-left',
            'rss' => 'FontAwesome:rss',
            'search-save' => 'FontAwesome:floppy-disk',
            'sign-in' => 'Image:user_account_white.svg',
            'status-available' => 'FontAwesome:check',
            'status-unavailable' => 'FontAwesome:remove',
            'status-uncertain' => 'FontAwesome:circle',
            'status-ordered' => 'FontAwesome:cart-shopping',
            'status-unknown' => 'FontAwesome:circle',
            'ui-delete' => 'FontAwesome:trash',
            'ui-reset-search' => 'FontAwesome:times-circle',
            'user-list-add' => 'FontAwesome:save',
        )
    ),
    'js' => array(
        'thulb.js',
        'jquery-ui.min.js',
    ),
);
