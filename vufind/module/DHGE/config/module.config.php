<?php
namespace DHGE\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            \DHGE\Controller\MyResearchController::class => \VuFind\Controller\AbstractBaseFactory::class
        ),
        'aliases' => array(
            'MyResearch' => \DHGE\Controller\MyResearchController::class,
            'myresearch' => \DHGE\Controller\MyResearchController::class
        )
    ),
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
            'ajaxhandler' => array(
                'factories' => array(
                    \DHGE\AjaxHandler\GetItemStatuses::class => \VuFind\AjaxHandler\GetItemStatusesFactory::class,
                ),
                'aliases' => array(
                    \ThULB\AjaxHandler\GetItemStatuses::class => \DHGE\AjaxHandler\GetItemStatuses::class,
                )
            ),
            'ils_driver' => array(
                'factories' => array(
                    'DHGE\ILS\Driver\PAIA' => 'DHGE\ILS\Driver\Factory::getPAIA',
                ),
                'aliases' => array(
                    'VuFind\ILS\Driver\PAIA' => 'DHGE\ILS\Driver\PAIA'
                )
            ),
            'recorddriver' => array(
                'factories' => array(
                    \DHGE\RecordDriver\SolrVZGRecord::class => 'DHGE\RecordDriver\Factory::getSolrMarc',
                ),
                'aliases' => array(
                    \ThULB\RecordDriver\SolrVZGRecord::class => \DHGE\RecordDriver\SolrVZGRecord::class
                ),
                'delegators' => array(
                    \DHGE\RecordDriver\SolrVZGRecord::class => ['VuFind\RecordDriver\IlsAwareDelegatorFactory'],
                )
            ),
        ),
    ),
);

return $config;