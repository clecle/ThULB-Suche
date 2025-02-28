<?php
return array(
    'extends' => 'thulb-bs5',
    'favicon' => 'logo_favicon.png',
    'helpers' => array (
        'factories' => array (
            \DHGE\View\Helper\Root\AvailabilityStatus::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            \DHGE\View\Helper\Root\DoiLinker::class => 'DHGE\View\Helper\Root\Factory::getDoiLinker',
            \DHGE\View\Helper\Root\Session::class => \DHGE\View\Helper\Root\SessionFactory::class,
            \DHGE\View\Helper\Record\OnlineContent::class => \ThULB\View\Helper\Record\OnlineContentFactory::class,
        ),
        'aliases' => array(
            'availabilityStatus' => \DHGE\View\Helper\Root\AvailabilityStatus::class,
            'dhge_doiLinker' => \DHGE\View\Helper\Root\DoiLinker::class,
            'dhge_session' => \DHGE\View\Helper\Root\Session::class,
            \ThULB\View\Helper\Record\OnlineContent::class => \DHGE\View\Helper\Record\OnlineContent::class,
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
//            'cart' => 'FontAwesome:star-o',
//            'cart-add' => 'FontAwesome:star-o',
//            'cart-remove' => 'FontAwesome:star',
//            'cite' => 'FontAwesome:quote-left',
//            'export' => 'FontAwesome:share',
//            'external-link' => 'FontAwesome:arrow-up-right-from-square',
//            'facet-checked' => 'FontAwesome:check',
//            'id-card' => 'FontAwesome:id-card-o',
//            'ill' => 'FontAwesome:book',
//            'information' => 'FontAwesome:info',
//            'lock' => 'FontAwesome:lock',
            'my-account' => 'FontAwesome:user',
//            'page-next' => 'FontAwesome:angles-right',
//            'page-prev' => 'FontAwesome:angles-left',
//            'rss' => 'FontAwesome:rss',
//            'search-save' => 'FontAwesome:floppy-disk',
            'sign-in' => 'FontAwesome:user',
//            'status-available' => 'FontAwesome:check',
//            'status-unavailable' => 'FontAwesome:remove',
//            'status-uncertain' => 'FontAwesome:circle',
//            'status-unknown' => 'FontAwesome:circle',
//            'ui-delete' => 'FontAwesome:trash',
//            'ui-reset-search' => 'FontAwesome:times-circle',
//            'user-list-add' => 'FontAwesome:save',
        )
    ),
);
