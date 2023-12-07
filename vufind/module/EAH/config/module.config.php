<?php
namespace EAH\Module\Configuration;

$config = array(
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
    'view_helpers' => array(
        'invokables' => array(
            'thulb_holdingHelper' => \EAH\View\Helper\Record\HoldingHelper::class,
        )
    )
);

return $config;