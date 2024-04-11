<?php
namespace EAH\Module\Configuration;

$config = array(
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