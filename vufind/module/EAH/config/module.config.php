<?php
namespace EAH\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            \EAH\Controller\RequestController::class => \VuFind\Controller\AbstractBaseWithConfigFactory::class,
        ),
        'aliases' => array(
            'request' => \EAH\Controller\RequestController::class,
            'Request' => \EAH\Controller\RequestController::class,
        )
    ),
    'controller_plugins' => array (
        'factories' => array(
            \EAH\Controller\Plugin\IlsRecords::class => \VuFind\Controller\Plugin\IlsRecordsFactory::class
        ),
        'aliases' => array(
            'ilsRecords' => \EAH\Controller\Plugin\IlsRecords::class,
            \VuFind\Controller\Plugin\IlsRecords::class => \EAH\Controller\Plugin\IlsRecords::class,
        )
    ),
    'vufind' => array(
        'plugin_managers' => array(
            'pdf' => array(
                'factories' => array (
                    \EAH\PDF\JournalRequest::class => \ThULB\PDF\PDFFactory::class,
                ),
                'aliases' => array(
                    \ThULB\PDF\JournalRequest::class => \EAH\PDF\JournalRequest::class,
                )
            ),
            'recorddriver' => array(
                'factories' => array(
                    \EAH\RecordDriver\SolrVZGRecord::class => \ThULB\RecordDriver\SolrVZGRecordFactory::class,
                ),
                'aliases' => array(
                    \ThULB\RecordDriver\SolrVZGRecord::class => \EAH\RecordDriver\SolrVZGRecord::class
                ),
                'delegators' => array(
                    \EAH\RecordDriver\SolrVZGRecord::class => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                )
            ),
        ),
    ),
);

return $config;