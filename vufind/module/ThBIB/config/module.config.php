<?php
namespace ThBIB\Module\Configuration;

$config = array(
    'vufind' => array(
        'plugin_managers' => array(
            'recommend' => array(
                'factories' => array(
                    'ThBIB\Recommend\SideFacets' => 'ThBIB\Recommend\Factory::getSideFacets',
                ),
                'aliases' => array(
                    'sidefacets' => 'ThBIB\Recommend\SideFacets',
                )
            ),
            'search_params' => array(
                'factories' => array(
                    'ThBIB\Search\Solr\Params' => 'VuFind\Search\Solr\ParamsFactory'
                ),
                'aliases' => array(
                    'solr' => 'ThBIB\Search\Solr\Params'
                )
            ),
            'search_results' => array(
                'factories' => array(
                    'VuFind\Search\Summon\Results' => 'ThBIB\Search\Results\Factory::getSummon',
                    'VuFind\Search\Solr\Results' => 'ThBIB\Search\Results\Factory::getSolr'
                )
            )
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'ThBIB\Search\Solr\HierarchicalFacetHelper' => 'Zend\ServiceManager\Factory\InvokableFactory',
        ),
        'aliases' => array(
            'VuFind\HierarchicalFacetHelper' => 'ThBIB\Search\Solr\HierarchicalFacetHelper',
        )
    ),
    'view_helpers' => array(
        'invokables' => array(
            'thulb_removeThBibFilter' => 'ThBIB\View\Helper\Root\RemoveThBibFilter',
        ),
    ),
);

return $config;