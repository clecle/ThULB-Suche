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
        )
    ),
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
                    'summoncombined' => 'ThULB\Recommend\Factory::getSummonCombined',
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
                    'articlecl' => 'ThULB\RecordTab\Factory::getArticleCollectionList',
                    'nonarticlecl' => 'ThULB\RecordTab\Factory::getNonArticleCollectionList'
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
            'VuFind\RecordDriver\SolrDefault' => array(
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
                    'Description'   => null,
                    'Reviews' => null,
                    'Excerpt' => null,
                    'Details' => 'staffviewcombined'
                ),
                'backgroundLoadedTabs' => array('ArticleCollectionList', 'NonArticleCollectionList')
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
                'queriedCookie' => 'ThULB\Role\PermissionProvider\Factory::getQueriedCookie',
            )
        ),
    ),
);

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoute($config, 'MyResearch/ChangePasswordLink');

return $config;