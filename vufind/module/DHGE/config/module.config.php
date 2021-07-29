<?php
namespace DHGE\Module\Configuration;

$config = array(
    'service_manager' => array(
        'factories' => array(
            \DHGE\Auth\Manager::class => \VuFind\Auth\ManagerFactory::class,
        ),
        'aliases' => array(
            \ThULB\Auth\Manager::class => \DHGE\Auth\Manager::class,
        )
    ),
    'vufind' => array(
        'plugin_managers' => array(
            'ils_driver' => array(
                'factories' => array(
                    'DHGE\ILS\Driver\PAIA' => 'DHGE\ILS\Driver\Factory::getPAIA',
                ),
                'aliases' => array(
                    'VuFind\ILS\Driver\PAIA' => 'DHGE\ILS\Driver\PAIA'
                )
            ),
        ),
    ),
);

return $config;