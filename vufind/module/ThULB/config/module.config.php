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
        ),
        'aliases' => array(
            'dynMessages' => 'ThULB\Controller\DynMessagesController',
            'DynMessages' => 'ThULB\Controller\DynMessagesController',
        )
    ),
    'service_manager' => [
        'factories' => [
            'ThULB\Mailer\Mailer' => 'ThULB\Mailer\Factory',
        ],
        'aliases' => array(
            'VuFind\Mailer' => 'ThULB\Mailer\Mailer',
            'VuFind\Mailer\Mailer' => 'ThULB\Mailer\Mailer',
        )
    ],
    'vufind' => array(
        'plugin_managers' => array(
            'ajaxhandler' => array(
                'factories' => array(
                    'ThULB\AjaxHandler\GetResultCount' => 'ThULB\AjaxHandler\GetResultCountFactory',
                    'ThULB\AjaxHandler\HideMessage' => 'ThULB\AjaxHandler\HideMessageFactory',
                ),
                'aliases' => array(
                    'getResultCount' => 'ThULB\AjaxHandler\GetResultCount',
                    'hideMessage' => 'ThULB\AjaxHandler\HideMessage',
                )
            ),
            'db_row' => array(
                'factories' => array(
                    'VuFind\Db\Row\User' => 'ThULB\Db\Row\Factory'
                ),
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
                ),
                'aliases' => array(
                    'summoncombined' => 'ThULB\Recommend\SummonCombined',
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
//                    'ThULB\RecordTab\RecordLinkCollectionList' => 'ThULB\RecordTab\Factory::getRecordLinkCollectionList'
                ),
                'aliases' => array(
                    'articlecl' => 'ThULB\RecordTab\ArticleCollectionList',
                    'nonarticlecl' => 'ThULB\RecordTab\NonArticleCollectionList',
//                    'relatedcl' => 'ThULB\RecordTab\RecordLinkCollectionList'
                ),
                'invokables' => array(
                    'staffviewcombined' => 'ThULB\RecordTab\StaffViewCombined'
                )
            ),
            'search_results' => array(
                'factories' => array(
                    'VuFind\Search\Summon\Results' => 'ThULB\Search\Results\Factory::getSummon',
                    'VuFind\Search\Solr\Results' => 'ThULB\Search\Results\Factory::getSolr'
                )
            )
        ),
        'recorddriver_tabs' => array(
            'VuFind\RecordDriver\RecordDefault' => array(
                'tabs' => array(
                    'Similar' => null,
                    'ArticleCollectionList' => 'articlecl',
                    'NonArticleCollectionList' => 'nonarticlecl',
                    'Description'   => null,
                    'Reviews' => null,
                    'Excerpt' => null
                ),
                'backgroundLoadedTabs' => array('ArticleCollectionList', 'NonArticleCollectionList')
            ),
            'VuFind\RecordDriver\SolrMarc' => array(
                'tabs' => array(
                    'Similar' => null,
                    'ArticleCollectionList' => 'articlecl',
                    'NonArticleCollectionList' => 'nonarticlecl',
//                    'RelatedResources' => 'relatedcl',
                    'Description'   => null,
                    'Reviews' => null,
                    'Excerpt' => null,
                    'Details' => 'staffviewcombined'
                ),
                'backgroundLoadedTabs' => array('ArticleCollectionList', 'NonArticleCollectionList' /* , 'RelatedResources' */ )
            ),
            'VuFind\RecordDriver\Summon' => array(
                'tabs' => array(
                    'Description' => null,
                    'Reviews' => null,
                    'Excerpt' => null
                ),
                'defaultTab' => null
            )
        )
    ),
    'view_helpers' => array(
        'invokables' => array(
            'thulb_metaDataHelper' => 'ThULB\View\Helper\Record\MetaDataHelper',
            'thulb_holdingHelper' => 'ThULB\View\Helper\Record\HoldingHelper',
            'thulb_serverType' => 'ThULB\View\Helper\Root\ServerType',
            'thulb_removeZWNJ' => 'ThULB\View\Helper\Root\RemoveZWNJ'
        ),
    ),

    // Authorization configuration:
    'zfc_rbac' => array(
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

return $config;