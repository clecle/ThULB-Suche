<?php
namespace ThULB\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories'    => array(
            'VuFind\Controller\CartController' => 'ThULB\Controller\Factory::getCartController',
            'VuFind\Controller\MyResearchController' => 'ThULB\Controller\Factory::getMyResearchController',
            'VuFind\Controller\SummonController' => 'ThULB\Controller\Factory::getSummonController',
            'VuFind\Controller\SummonrecordController' => 'ThULB\Controller\Factory::getSummonrecordController',
            'ThULB\Controller\DynMessagesController' => 'ThULB\Controller\Factory::getDynMessagesController',
            \ThULB\Controller\RequestController::class => \VuFind\Controller\AbstractBaseWithConfigFactory::class,
            'ThULB\Controller\SearchController' => 'VuFind\Controller\AbstractBaseFactory',
        ),
        'aliases' => array(
            'dynMessages' => 'ThULB\Controller\DynMessagesController',
            'DynMessages' => 'ThULB\Controller\DynMessagesController',
            'request' => \ThULB\Controller\RequestController::class,
            'Request' => \ThULB\Controller\RequestController::class,
            'VuFind\Controller\SearchController' => 'ThULB\Controller\SearchController',
        )
    ),
    'service_manager' => [
        'factories' => [
            'ThULB\Mailer\Mailer' => 'ThULB\Mailer\Factory',
            'ThULB\Record\Loader' => 'VuFind\Record\LoaderFactory',
            'ThULB\Search\Facets\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'ThULB\Search\Solr\HierarchicalFacetHelper' => 'Laminas\ServiceManager\Factory\InvokableFactory',
        ],
        'aliases' => array(
            'VuFind\HierarchicalFacetHelper' => 'ThULB\Search\Solr\HierarchicalFacetHelper',
            'VuFind\Search\Solr\HierarchicalFacetHelper' => 'ThULB\Search\Solr\HierarchicalFacetHelper',
            'VuFind\Mailer' => 'ThULB\Mailer\Mailer',
            'VuFind\Mailer\Mailer' => 'ThULB\Mailer\Mailer',
            'VuFind\Record\Loader' => 'ThULB\Record\Loader',
        )
    ],
    'vufind' => array(
        'plugin_managers' => array(
            'ajaxhandler' => array(
                'factories' => array(
                    \ThULB\AjaxHandler\FulltextLookup::class => \ThULB\AjaxHandler\FulltextLookupFactory::class,
                    \ThULB\AjaxHandler\GetItemStatuses::class => \VuFind\AjaxHandler\GetItemStatusesFactory::class,
                    'ThULB\AjaxHandler\GetResultCount' => 'ThULB\AjaxHandler\GetResultCountFactory',
                    'ThULB\AjaxHandler\HideMessage' => 'ThULB\AjaxHandler\HideMessageFactory',
                    \ThULB\AjaxHandler\VpnWarning::class => \ThULB\AjaxHandler\VpnWarningFactory::class,
                ),
                'aliases' => array(
                    'fulltextLookup' => \ThULB\AjaxHandler\FulltextLookup::class,
                    'getResultCount' => 'ThULB\AjaxHandler\GetResultCount',
                    'hideMessage' => 'ThULB\AjaxHandler\HideMessage',
                    'vpnWarning' => \ThULB\AjaxHandler\VpnWarning::class,
                    \VuFind\AjaxHandler\GetItemStatuses::class => \ThULB\AjaxHandler\GetItemStatuses::class,
                )
            ),
            'content_covers' => array(
                'factories' => array(
                    \ThULB\Content\Covers\Google::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                ),
                'aliases' => array(
                    'google' => \ThULB\Content\Covers\Google::class
                )
            ),
            'db_row' => array(
                'factories' => array(
                    'VuFind\Db\Row\User' => 'ThULB\Db\Row\Factory'
                ),
            ),
            'doilinker' => array(
                'factories' => array (
                    'ThULB\DoiLinker\Unpaywall' => 'VuFind\DoiLinker\UnpaywallFactory',
                ),
                'aliases' => array (
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
                    'VuFind\ILS\Driver\PAIA' => 'ThULB\ILS\Driver\Factory::getPAIA',
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
                ),
                'aliases' => array(
                    'class_local_iln' => \ThULB\Search\Facets\ThBIBFacet::class
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
            'thulb_metaDataHelper' => 'ThULB\View\Helper\Record\MetaDataHelper',
            'thulb_holdingHelper' => 'ThULB\View\Helper\Record\HoldingHelper',
            'thulb_serverType' => 'ThULB\View\Helper\Root\ServerType',
            'thulb_removeZWNJ' => 'ThULB\View\Helper\Root\RemoveZWNJ',
            'thulb_removeThBibFilter' => 'ThULB\View\Helper\Root\RemoveThBibFilter',
            'thulb_doiLinker' => \ThULB\View\Helper\Root\DoiLinker::class,
        ),
    ),

    // Authorization configuration:
    'lmc_rbac' => array(
        'vufind_permission_provider_manager' => array(
            'factories' => array(
                'ThULB\Role\PermissionProvider\QueriedCookie' => 'ThULB\Role\PermissionProvider\Factory::getQueriedCookie',
                'ThULB\Role\PermissionProvider\IpRange' => 'ThULB\Role\PermissionProvider\Factory::getIpRange',
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
$routeGenerator->addDynamicRoute($config, 'Request/Journal', 'Request', 'Journal/[:id]');

return $config;