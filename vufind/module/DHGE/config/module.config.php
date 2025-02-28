<?php
namespace DHGE\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            \DHGE\Controller\MyResearchController::class => \VuFind\Controller\MyResearchControllerFactory::class,
            \DHGE\Controller\RecordController::class => \VuFind\Controller\AbstractBaseWithConfigFactory::class,
        ),
        'aliases' => array(
            'MyResearch' => \DHGE\Controller\MyResearchController::class,
            'myresearch' => \DHGE\Controller\MyResearchController::class,
            \ThULB\Controller\RecordController::class => \DHGE\Controller\RecordController::class
        )
    ),
    'service_manager' => array(
        'factories' => array(
            \DHGE\Auth\Manager::class => \VuFind\Auth\ManagerFactory::class,
            \DHGE\ILS\Connection::class => \VuFind\ILS\ConnectionFactory::class,
        ),
        'aliases' => array(
            \ThULB\Auth\Manager::class => \DHGE\Auth\Manager::class,
            \ThULB\ILS\Connection::class => \DHGE\ILS\Connection::class,
        )
    ),
    'vufind' => array(
        'plugin_managers' => array(
            'ajaxhandler' => array(
                'factories' => array(
                    \DHGE\AjaxHandler\GetItemStatuses::class => \ThULB\AjaxHandler\GetItemStatusesFactory::class,
                ),
                'aliases' => array(
                    \ThULB\AjaxHandler\GetItemStatuses::class => \DHGE\AjaxHandler\GetItemStatuses::class,
                )
            ),
            'ils_driver' => array(
                'factories' => array(
                    \DHGE\ILS\Driver\PAIA::class => \VuFind\ILS\Driver\PAIAFactory::class,
                ),
                'aliases' => array(
                    \VuFind\ILS\Driver\PAIA::class => \DHGE\ILS\Driver\PAIA::class,
                )
            ),
            'recorddriver' => array(
                'factories' => array(
                    \DHGE\RecordDriver\SolrVZGRecord::class => \ThULB\RecordDriver\SolrVZGRecordFactory::class,
                ),
                'aliases' => array(
                    \ThULB\RecordDriver\SolrVZGRecord::class => \DHGE\RecordDriver\SolrVZGRecord::class
                ),
                'delegators' => array(
                    \DHGE\RecordDriver\SolrVZGRecord::class => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                )
            ),
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'thulb_holdingHelper' => \DHGE\View\Helper\Record\HoldingHelper::class,
        ),
    ),
);

return $config;
