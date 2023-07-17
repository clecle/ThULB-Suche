<?php
namespace ThULB\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            'VuFind\Controller\CartController' => 'ThULB\Controller\Factory::getCartController',
            \ThULB\Controller\CoverController::class => \VuFind\Controller\CoverControllerFactory::class,
            \ThULB\Controller\HoldsController::class => \VuFind\Controller\HoldsControllerFactory::class,
            \ThULB\Controller\MyResearchController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \ThULB\Controller\RecordController::class => \VuFind\Controller\AbstractBaseWithConfigFactory::class,
            \ThULB\Controller\RequestController::class => \VuFind\Controller\AbstractBaseWithConfigFactory::class,
            \ThULB\Controller\SearchController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \ThULB\Controller\SummonController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \ThULB\Controller\SummonrecordController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \ThULB\Controller\VpnController::class => \VuFind\Controller\AbstractBaseFactory::class,
        ),
        'aliases' => array(
            'Holds' => \ThULB\Controller\HoldsController::class,
            'holds' => \ThULB\Controller\HoldsController::class,
            'request' => \ThULB\Controller\RequestController::class,
            'Request' => \ThULB\Controller\RequestController::class,
            'Vpn' => \ThULB\Controller\VpnController::class,
            'vpn' => \ThULB\Controller\VpnController::class,
            'VPN' => \ThULB\Controller\VpnController::class,
            \VuFind\Controller\SearchController::class => \ThULB\Controller\SearchController::class,
            \VuFind\Controller\RecordController::class => \ThULB\Controller\RecordController::class,
            \VuFind\Controller\HoldsController::class => \ThULB\Controller\HoldsController::class,
            \VuFind\Controller\MyResearchController::class => \ThULB\Controller\MyResearchController::class,
            \VuFind\Controller\SummonController::class => \ThULB\Controller\SummonController::class,
            \VuFind\Controller\SummonrecordController::class => \ThULB\Controller\SummonrecordController::class,
            \VuFind\Controller\CoverController::class => \ThULB\Controller\CoverController::class,
        )
    ),
    'controller_plugins' => array (
        'factories' => array(
            \ThULB\Controller\Plugin\IlsRecords::class => \VuFind\Controller\Plugin\IlsRecordsFactory::class
        ),
        'aliases' => array(
            'ilsRecords' => \ThULB\Controller\Plugin\IlsRecords::class,
            \VuFind\Controller\Plugin\IlsRecords::class => \ThULB\Controller\Plugin\IlsRecords::class,
        )
    ),
    'service_manager' => [
        'factories' => [
            \ThULB\Auth\Manager::class => \VuFind\Auth\ManagerFactory::class,
            \ThULB\Content\LocationData\ThULB::class => \ThULB\Content\LocationData\ThULBFactory::class,
            \ThULB\Mailer\Mailer::class => \ThULB\Mailer\Factory::class,
            'ThULB\Record\Loader' => 'VuFind\Record\LoaderFactory',
            'ThULB\Search\Facets\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'ThULB\Search\Solr\HierarchicalFacetHelper' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'ThULB\Cover\Loader' => 'VuFind\Cover\LoaderFactory',
        ],
        'aliases' => array(
            \VuFind\Auth\Manager::class => \ThULB\Auth\Manager::class,
            'VuFind\HierarchicalFacetHelper' => 'ThULB\Search\Solr\HierarchicalFacetHelper',
            'VuFind\Search\Solr\HierarchicalFacetHelper' => 'ThULB\Search\Solr\HierarchicalFacetHelper',
            'VuFind\Mailer' => 'ThULB\Mailer\Mailer',
            'VuFind\Mailer\Mailer' => 'ThULB\Mailer\Mailer',
            'VuFind\Record\Loader' => 'ThULB\Record\Loader',
            'VuFind\Cover\Loader' => 'ThULB\Cover\Loader',
        )
    ],
    'vufind' => array(
        'plugin_managers' => array(
            'ajaxhandler' => array(
                'factories' => array(
                    \ThULB\AjaxHandler\FulltextLookup::class => \ThULB\AjaxHandler\FulltextLookupFactory::class,
                    \ThULB\AjaxHandler\OnlineContentLookup::class => \ThULB\AjaxHandler\OnlineContentLookupFactory::class,
                    \ThULB\AjaxHandler\GetItemStatuses::class => \VuFind\AjaxHandler\GetItemStatusesFactory::class,
                    'ThULB\AjaxHandler\GetResultCount' => 'ThULB\AjaxHandler\GetResultCountFactory',
                    'ThULB\AjaxHandler\HideMessage' => 'ThULB\AjaxHandler\HideMessageFactory',
                    \ThULB\AjaxHandler\VpnWarning::class => \ThULB\AjaxHandler\VpnWarningFactory::class,
                ),
                'aliases' => array(
                    'fulltextLookup' => \ThULB\AjaxHandler\FulltextLookup::class,
                    'onlineContentLookup' => \ThULB\AjaxHandler\OnlineContentLookup::class,
                    'getResultCount' => 'ThULB\AjaxHandler\GetResultCount',
                    'hideMessage' => 'ThULB\AjaxHandler\HideMessage',
                    'vpnWarning' => \ThULB\AjaxHandler\VpnWarning::class,
                    \VuFind\AjaxHandler\GetItemStatuses::class => \ThULB\AjaxHandler\GetItemStatuses::class,
                )
            ),
            'auth' => array(
                'factories' => array(
                    \ThULB\Auth\ILS::class => \VuFind\Auth\ILSFactory::class,
                ),
                'aliases' => array(
                    'ils' => \ThULB\Auth\ILS::class,
                    \VuFind\Auth\ILS::class => \ThULB\Auth\ILS::class,
                )
            ),
            'content_covers' => array(
                'factories' => array(
                    \ThULB\Content\Covers\Google::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                    \ThULB\Content\Covers\IIIF::class => \VuFind\Service\ServiceWithConfigIniFactory::class,
                ),
                'aliases' => array(
                    'google' => \ThULB\Content\Covers\Google::class,
                    'iiif' => \ThULB\Content\Covers\IIIF::class
                )
            ),
            'db_row' => array(
                'factories' => array(
                    'VuFind\Db\Row\User' => 'ThULB\Db\Row\Factory'
                ),
            ),
            'doilinker' => array(
                'factories' => array (
                    \ThULB\DoiLinker\BrowZine::class => \VuFind\DoiLinker\BrowZineFactory::class,
                    'ThULB\DoiLinker\Unpaywall' => 'VuFind\DoiLinker\UnpaywallFactory',
                ),
                'aliases' => array (
                    \VuFind\DoiLinker\BrowZine::class => \ThULB\DoiLinker\BrowZine::class,
                    'VuFind\DoiLinker\Unpaywall' => 'ThULB\DoiLinker\Unpaywall',
                )
            ),
            'hierarchy_treedataformatter' => array(
                'invokables' => array(
                    'VuFind\Hierarchy\TreeDataFormatter\Json' => 'ThULB\Hierarchy\TreeDataFormatter\Json'
                ),
            ),
            'hierarchy_treedatasource' => array(
                'factories' => array(
                    'VuFind\Hierarchy\TreeDataSource\Solr' => 'ThULB\Hierarchy\TreeDataSource\Factory::getSolr',
                )
            ),
            'ils_driver' => array(
                'factories' => array(
                    \ThULB\ILS\Driver\PAIA::class => \VuFind\ILS\Driver\PAIAFactory::class,
                    \ThULB\ILS\Driver\Sera::class => \ThULB\ILS\Driver\SeraFactory::class,
                ),
                'aliases' => array(
                    \VuFind\ILS\Driver\PAIA::class => \ThULB\ILS\Driver\PAIA::class,
                    'sera' => \ThULB\ILS\Driver\Sera::class,
                )
            ),
            'recommend' => array(
                'factories' => array(
                    'ThULB\Recommend\SummonCombined' => 'ThULB\Recommend\Factory::getSummonCombined',
                    'ThULB\Recommend\SideFacets' => 'ThULB\Recommend\Factory::getSideFacets',
                ),
                'aliases' => array(
                    'summoncombined' => 'ThULB\Recommend\SummonCombined',
                    'sidefacets' => 'ThULB\Recommend\SideFacets',
                ),
                'invokables' => array(
                    'summoncombineddeferred' => 'ThULB\Recommend\SummonCombinedDeferred',
                )
            ),
            'recorddriver' => array(
                'factories' => array(
                    'ThULB\RecordDriver\SolrVZGRecord' => 'ThULB\RecordDriver\Factory::getSolrMarc',
                    'VuFind\RecordDriver\Summon' => 'ThULB\RecordDriver\Factory::getSummon'
                ),
                'aliases' => array(
                    'solrmarc' => 'ThULB\RecordDriver\SolrVZGRecord',
                ),
                'delegators' => array(
                    'ThULB\RecordDriver\SolrVZGRecord' => ['VuFind\RecordDriver\IlsAwareDelegatorFactory'],
                )
            ),
            'record_fallbackloader' => array (
                'factories' => array (
                    \VuFind\Record\FallbackLoader\Summon::class => \VuFind\Record\FallbackLoader\SummonFactory::class,
                ),
                'aliases' => array (
                    'summon' => null
                )
            ),
            'recordtab' => array(
                'factories' => array(
                    'ThULB\RecordTab\ArticleCollectionList' => 'ThULB\RecordTab\Factory::getArticleCollectionList',
                    'ThULB\RecordTab\NonArticleCollectionList' => 'ThULB\RecordTab\Factory::getNonArticleCollectionList',
                    'ThULB\RecordTab\Access' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                ),
                'aliases' => array(
                    'articlecl' => 'ThULB\RecordTab\ArticleCollectionList',
                    'nonarticlecl' => 'ThULB\RecordTab\NonArticleCollectionList',
                    'access' => 'ThULB\RecordTab\Access'
                ),
                'invokables' => array(
                    'staffviewcombined' => 'ThULB\RecordTab\StaffViewCombined'
                )
            ),
            'search_facets' => array(
                'factories' => array(
                    \ThULB\Search\Facets\ThBIBFacet::class => \ThULB\Search\Facets\FacetFactory::class,
                    \ThULB\Search\Facets\LocationFacet::class => \ThULB\Search\Facets\FacetFactory::class,
                ),
                'aliases' => array(
                    'class_local_iln' => \ThULB\Search\Facets\ThBIBFacet::class,
                    'standort_iln_str_mv' => \ThULB\Search\Facets\LocationFacet::class
                )
            ),
            'search_params' => array(
                'factories' => array(
                    'ThULB\Search\Solr\Params' => \ThULB\Search\Solr\ParamsFactory::class,
                    'ThULB\Search\Summon\Params' => 'VuFind\Search\Params\ParamsFactory'
                ),
                'aliases' => array(
                    'solr' => 'ThULB\Search\Solr\Params',
                    'summon' => 'ThULB\Search\Summon\Params'
                )
            ),
            'search_results' => array(
                'factories' => array(
                    'VuFind\Search\Summon\Results' => 'ThULB\Search\Results\Factory::getSummon',
                    'VuFind\Search\Solr\Results' => 'ThULB\Search\Results\Factory::getSolr'
                )
            ),
            'search_backend' => array(
                'factories' => array(
                    'Solr' => \ThULB\Search\Factory\SolrDefaultBackendFactory::class
                )
            )
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'thulb_doiLinker' => \ThULB\View\Helper\Root\DoiLinker::class,
            'thulb_holdingHelper' => \ThULB\View\Helper\Record\HoldingHelper::class,
            'thulb_locationData' => \ThULB\View\Helper\Root\LocationData::class,
//            'thulb_metaDataHelper' => \ThULB\View\Helper\Record\MetaDataHelper::class,
            'thulb_onlineContent' => \ThULB\View\Helper\Record\OnlineContent::class,
//            'thulb_removeThBibFilter' => \ThULB\View\Helper\Root\RemoveThBibFilter::class,
            'thulb_removeZWNJ' => \ThULB\View\Helper\Root\RemoveZWNJ::class,
            'thulb_sera' => \ThULB\View\Helper\Record\SeraHelper::class,
            'thulb_serverType' => \ThULB\View\Helper\Root\ServerType::class,
            'thulb_serviceDesk' => \ThULB\View\Helper\Root\ServiceDesk::class
        ),
    ),

    // Authorization configuration:
    'lmc_rbac' => array(
        'vufind_permission_provider_manager' => array(
            'factories' => array(
                'ThULB\Role\PermissionProvider\QueriedCookie' => 'ThULB\Role\PermissionProvider\Factory::getQueriedCookie',
                'ThULB\Role\PermissionProvider\IpRange' => \VuFind\Role\PermissionProvider\IpRangeFactory::class,
            ),
            'aliases' => array(
                'queriedCookie' => 'ThULB\Role\PermissionProvider\QueriedCookie',
                'ipRange' => 'ThULB\Role\PermissionProvider\IpRange'
            )
        ),
    ),
);

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoute($config, 'MyResearch/ChangePasswordLink');
$routeGenerator->addStaticRoute($config, 'MyResearch/letterOfAuthorization');
$routeGenerator->addDynamicRoute($config, 'Request/Journal', 'Request', 'Journal/[:id]');
$routeGenerator->addDynamicRoute($config, 'Location', 'Location', 'Information/[:id]');

return $config;