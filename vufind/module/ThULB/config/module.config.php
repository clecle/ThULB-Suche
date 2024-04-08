<?php
namespace ThULB\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => array(
            \ThULB\Controller\CartController::class => \VuFind\Controller\CartControllerFactory::class,
            \ThULB\Controller\CoverController::class => \VuFind\Controller\CoverControllerFactory::class,
            \ThULB\Controller\HoldsController::class => \VuFind\Controller\HoldsControllerFactory::class,
            \ThULB\Controller\ILLController::class => \VuFind\Controller\AbstractBaseFactory::class,
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
            'ill' => \ThULB\Controller\ILLController::class,
            'ILL' => \ThULB\Controller\ILLController::class,
            'request' => \ThULB\Controller\RequestController::class,
            'Request' => \ThULB\Controller\RequestController::class,
            'Vpn' => \ThULB\Controller\VpnController::class,
            'vpn' => \ThULB\Controller\VpnController::class,
            'VPN' => \ThULB\Controller\VpnController::class,
            \VuFind\Controller\CartController::class =>  \ThULB\Controller\CartController::class,
            \VuFind\Controller\HoldsController::class => \ThULB\Controller\HoldsController::class,
            \VuFind\Controller\MyResearchController::class => \ThULB\Controller\MyResearchController::class,
            \VuFind\Controller\RecordController::class => \ThULB\Controller\RecordController::class,
            \VuFind\Controller\SearchController::class => \ThULB\Controller\SearchController::class,
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
    'service_manager' => array(
        'factories' => array(
            \ThULB\Auth\Manager::class => \VuFind\Auth\ManagerFactory::class,
            \ThULB\Cache\Manager::class => \VuFind\Cache\ManagerFactory::class,
            \ThULB\Content\LocationData\ThULB::class => \ThULB\Content\LocationData\ThULBFactory::class,
            \ThULB\Cover\Loader::class => \VuFind\Cover\LoaderFactory::class,
            \ThULB\Mailer\Mailer::class => \ThULB\Mailer\Factory::class,
            \ThULB\Record\Loader::class => \VuFind\Record\LoaderFactory::class,
            \ThULB\Search\Facets\PluginManager::class => \VuFind\ServiceManager\AbstractPluginManagerFactory::class,
            \ThULB\Search\Solr\HierarchicalFacetHelper::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
        ),
        'aliases' => array(
            \VuFind\Auth\Manager::class => \ThULB\Auth\Manager::class,
            \VuFind\Cache\Manager::class => \ThULB\Cache\Manager::class,
            \VuFind\Cover\Loader::class => \ThULB\Cover\Loader::class,
            'VuFind\HierarchicalFacetHelper' => \ThULB\Search\Solr\HierarchicalFacetHelper::class,
            'VuFind\Mailer' => \ThULB\Mailer\Mailer::class,
            \VuFind\Mailer\Mailer::class => \ThULB\Mailer\Mailer::class,
            \VuFind\Record\Loader::class => \ThULB\Record\Loader::class,
            \VuFind\Search\Solr\HierarchicalFacetHelper::class => \ThULB\Search\Solr\HierarchicalFacetHelper::class,
        )
    ),
    'vufind' => array(
        'plugin_managers' => array(
            'ajaxhandler' => array(
                'factories' => array(
                    \ThULB\AjaxHandler\FulltextLookup::class => \ThULB\AjaxHandler\FulltextLookupFactory::class,
                    \ThULB\AjaxHandler\GetItemStatuses::class => \VuFind\AjaxHandler\GetItemStatusesFactory::class,
                    \ThULB\AjaxHandler\GetResultCount::class => \ThULB\AjaxHandler\GetResultCountFactory::class,
                    \ThULB\AjaxHandler\HideMessage::class => \ThULB\AjaxHandler\HideMessageFactory::class,
                    \ThULB\AjaxHandler\AccessLookup::class => \ThULB\AjaxHandler\AccessLookupFactory::class,
                    \ThULB\AjaxHandler\VpnWarning::class => \ThULB\AjaxHandler\VpnWarningFactory::class,
                ),
                'aliases' => array(
                    'fulltextLookup' => \ThULB\AjaxHandler\FulltextLookup::class,
                    'getResultCount' => \ThULB\AjaxHandler\GetResultCount::class,
                    'hideMessage' => \ThULB\AjaxHandler\HideMessage::class,
                    'accessLookup' => \ThULB\AjaxHandler\AccessLookup::class,
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
                    \ThULB\Content\Covers\IIIF::class => \VuFind\Service\ServiceWithConfigIniFactory::class,
                ),
                'aliases' => array(
                    'iiif' => \ThULB\Content\Covers\IIIF::class
                )
            ),
            'db_row' => array(
                'factories' => array(
                    \VuFind\Db\Row\User::class => \ThULB\Db\Row\Factory::class
                ),
            ),
            'doilinker' => array(
                'factories' => array(
                    \ThULB\DoiLinker\BrowZine::class => \VuFind\DoiLinker\BrowZineFactory::class,
                    \ThULB\DoiLinker\Unpaywall::class => \VuFind\DoiLinker\UnpaywallFactory::class,
                ),
                'aliases' => array(
                    \VuFind\DoiLinker\BrowZine::class => \ThULB\DoiLinker\BrowZine::class,
                    \VuFind\DoiLinker\Unpaywall::class => \ThULB\DoiLinker\Unpaywall::class,
                )
            ),
            'ils_driver' => array(
                'factories' => array(
                    \ThULB\ILS\Driver\PAIA::class => \VuFind\ILS\Driver\PAIAFactory::class,
                    \ThULB\ILS\Driver\Sera::class => \ThULB\ILS\Driver\SeraFactory::class,
                    \ThULB\ILS\Driver\CBSUserdpo::class => \ThULB\ILS\Driver\CBSUserdpoFactory::class,
                ),
                'aliases' => array(
                    \VuFind\ILS\Driver\PAIA::class => \ThULB\ILS\Driver\PAIA::class,
                    'sera' => \ThULB\ILS\Driver\Sera::class,
                    'cbsuserdpo' => \ThULB\ILS\Driver\CBSUserdpo::class,
                )
            ),
            'recommend' => array(
                'factories' => array(
                    \ThULB\Recommend\SideFacets::class => \VuFind\Recommend\SideFacetsFactory::class,
                    \ThULB\Recommend\SummonCombined::class => \VuFind\Recommend\InjectResultsManagerFactory::class
                ),
                'aliases' => array(
                    'sidefacets' => \ThULB\Recommend\SideFacets::class,
                    'summoncombined' => \ThULB\Recommend\SummonCombined::class,
                ),
                'invokables' => array(
                    'summoncombineddeferred' => \ThULB\Recommend\SummonCombinedDeferred::class,
                )
            ),
            'recorddriver' => array(
                'factories' => array(
                    \ThULB\RecordDriver\SolrVZGRecord::class => \ThULB\RecordDriver\SolrVZGRecordFactory::class,
                    \ThULB\RecordDriver\Summon::class => \VuFind\RecordDriver\SummonFactory::class,
                ),
                'aliases' => array(
                    'solrmarc' => \ThULB\RecordDriver\SolrVZGRecord::class,
                    \VuFind\RecordDriver\Summon::class => \ThULB\RecordDriver\Summon::class,
                ),
                'delegators' => array(
                    \ThULB\RecordDriver\SolrVZGRecord::class => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                )
            ),
            'record_fallbackloader' => array(
                'aliases' => array(
                    'summon' => null
                )
            ),
            'recordtab' => array(
                'factories' => array(
                    \ThULB\RecordTab\Access::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                    \ThULB\RecordTab\ArticleCollectionList::class => \VuFind\RecordTab\CollectionListFactory::class,
                    \ThULB\RecordTab\NonArticleCollectionList::class => \VuFind\RecordTab\CollectionListFactory::class,
                ),
                'aliases' => array(
                    'access' => \ThULB\RecordTab\Access::class,
                    'articlecl' => \ThULB\RecordTab\ArticleCollectionList::class,
                    'nonarticlecl' => \ThULB\RecordTab\NonArticleCollectionList::class,
                ),
                'invokables' => array(
                    'staffviewcombined' => \ThULB\RecordTab\StaffViewCombined::class,
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
                    \ThULB\Search\Blender\Params::class => \ThULB\Search\Blender\ParamsFactory::class,
                    \ThULB\Search\Solr\Params::class => \ThULB\Search\Solr\ParamsFactory::class,
                    \ThULB\Search\Summon\Params::class => \VuFind\Search\Params\ParamsFactory::class
                ),
                'aliases' => array(
                    'blender' => \ThULB\Search\Blender\Params::class,
                    'solr' => \ThULB\Search\Solr\Params::class,
                    'summon' => \ThULB\Search\Summon\Params::class
                )
            ),
            'search_results' => array(
                'factories' => array(
                    \ThULB\Search\Blender\Results::class => \ThULB\Search\Solr\ResultsFactory::class,
                    \ThULB\Search\Solr\Results::class => \ThULB\Search\Solr\ResultsFactory::class,
                    \ThULB\Search\Summon\Results::class => \VuFind\Search\Results\ResultsFactory::class,
                ),
                'aliases' => array(
                    \VuFind\Search\Blender\Results::class => \ThULB\Search\Blender\Results::class,
                    \VuFind\Search\Solr\Results::class => \ThULB\Search\Solr\Results::class,
                    \VuFind\Search\Summon\Results::class => \ThULB\Search\Summon\Results::class,
                )
            ),
            'search_backend' => array(
                'factories' => array(
                    'Summon' => \ThULB\Search\Factory\SummonBackendFactory::class
                )
            )
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'thulb_holdingHelper' => \ThULB\View\Helper\Record\HoldingHelper::class,
            'thulb_locationData' => \ThULB\View\Helper\Root\LocationData::class,
            'thulb_serverType' => \ThULB\View\Helper\Root\ServerType::class,
            'thulb_removeZWNJ' => \ThULB\View\Helper\Root\RemoveZWNJ::class,
            'thulb_onlineContent' => \ThULB\View\Helper\Record\OnlineContent::class,
            'thulb_sera' => \ThULB\View\Helper\Record\SeraHelper::class,
            'thulb_serviceDesk' => \ThULB\View\Helper\Root\ServiceDesk::class,
            'thulb_userType' => \ThULB\View\Helper\Root\UserType::class
        ),
    ),

    // Authorization configuration:
    'lmc_rbac' => array(
        'vufind_permission_provider_manager' => array(
            'factories' => array(
                \ThULB\Role\PermissionProvider\IpRange::class => \VuFind\Role\PermissionProvider\IpRangeFactory::class,
                \ThULB\Role\PermissionProvider\QueriedCookie::class => 'ThULB\Role\PermissionProvider\Factory::getQueriedCookie',
            ),
            'aliases' => array(
                'ipRange' => \ThULB\Role\PermissionProvider\IpRange::class,
                'queriedCookie' => \ThULB\Role\PermissionProvider\QueriedCookie::class,
            )
        ),
    ),
);

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoute($config, 'ILL/chargecredits');
$routeGenerator->addStaticRoute($config, 'ILL/forgotpassword');
$routeGenerator->addStaticRoute($config, 'ILL/deleteaccount');
$routeGenerator->addStaticRoute($config, 'MyResearch/ChangePasswordLink');
$routeGenerator->addStaticRoute($config, 'MyResearch/letterOfAuthorization');
$routeGenerator->addNonTabRecordActions($config, ['OrderReserve']);

$routeGenerator->addDynamicRoute($config, 'Request/Journal', 'Request', 'Journal/[:id]');
$routeGenerator->addDynamicRoute($config, 'Location', 'Location', 'Information/[:id]');

return $config;