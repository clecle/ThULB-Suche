<?php
namespace DHGE\Module\Configuration;

$config = array(
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