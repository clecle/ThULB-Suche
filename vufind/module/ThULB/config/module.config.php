<?php
namespace ThULB\Module\Configuration;

$config = [
    'controllers' => [
        'factories'    => [
            'ajax' => 'ThULB\Controller\Factory::getAjaxController',
            'cart' => 'ThULB\Controller\Factory::getCartController',
            'my-research' => 'ThULB\Controller\Factory::getMyResearchController',
            'summon' => 'ThULB\Controller\Factory::getSummonController',
            'summonrecord' => 'ThULB\Controller\Factory::getSummonrecordController',
            'dynmessages' => 'ThULB\Controller\Factory::getDynMessagesController',
        ]
    ],
    'vufind' => [
        'plugin_managers' => [
            'db_row' => [
                'factories' => [
                    'user' => 'ThULB\Db\Row\Factory::getUser'
                ],
            ],
            'hierarchy_treedataformatter' => [
                'invokables' => [
                    'json' => 'ThULB\Hierarchy\TreeDataFormatter\Json'
                ],
            ],
            'hierarchy_treedatasource' => [
                'factories' => [
                    'solr' => 'ThULB\Hierarchy\TreeDataSource\Factory::getSolr',
                ]
            ],
            'ils_driver' => [
                'factories' => [
                    'paia' => 'ThULB\ILS\Driver\Factory::getPAIA'
                ]
            ],
            'recommend' => [
                'factories' => [
                    'summoncombined' => 'ThULB\Recommend\Factory::getSummonCombined',                    
                ],
                'invokables' => [
                    'summoncombineddeferred' => 'ThULB\Recommend\SummonCombinedDeferred',
                ]
            ],
            'recorddriver' => [
                'factories' => [
                    'solrmarc' => 'ThULB\RecordDriver\Factory::getSolrMarc',
                    'summon' => 'ThULB\RecordDriver\Factory::getSummon'
                ],
            ],
            'recordtab' => [
                'factories' => [
                    'articlecl' => 'ThULB\RecordTab\Factory::getArticleCollectionList',
                    'nonarticlecl' => 'ThULB\RecordTab\Factory::getNonArticleCollectionList'
                ],
                'invokables' => [
                    'staffviewcombined' => 'ThULB\RecordTab\StaffViewCombined'
                ]
            ],
            'search_results' => [
                'factories' => [
                    'summon' => 'ThULB\Search\Results\Factory::getSummon',
                    'solr' => 'ThULB\Search\Results\Factory::getSolr'
                ]
            ]
        ],
        'recorddriver_tabs' => [
            'VuFind\RecordDriver\SolrDefault' => [
                'tabs' => [
                    'Similar' => null,
                    'ArticleCollectionList' => 'articlecl',
                    'NonArticleCollectionList' => 'nonarticlecl',
                    'Description'   => null,
                    'Reviews' => null,
                    'Excerpt' => null
                ],
                'backgroundLoadedTabs' => ['ArticleCollectionList', 'NonArticleCollectionList']
            ],
            'VuFind\RecordDriver\SolrMarc' => [
                'tabs' => [
                    'Similar' => null,
                    'ArticleCollectionList' => 'articlecl',
                    'NonArticleCollectionList' => 'nonarticlecl',
                    'Description'   => null,
                    'Reviews' => null,
                    'Excerpt' => null,
                    'Details' => 'staffviewcombined'
                ],
                'backgroundLoadedTabs' => ['ArticleCollectionList', 'NonArticleCollectionList']
            ],
            'VuFind\RecordDriver\Summon' => [
                'tabs' => [
                    'Description' => null,
                    'Reviews' => null,
                    'Excerpt' => null
                ],
                'defaultTab' => null
            ]
        ]
    ],
    'view_helpers' => [
        'invokables' => [
            'thulb_metadatahelper' => 'ThULB\View\Helper\Record\MetaDataHelper',
            'thulb_holdinghelper' => 'ThULB\View\Helper\Record\HoldingHelper',
            'server_type' => 'ThULB\View\Helper\Root\ServerType',
            'thulb_removeZWNJ' => 'ThULB\View\Helper\Root\RemoveZWNJ'     // Helper to remove zero-width non-joiner characters from a string
        ],
    ],
    
    // Authorization configuration:
    'zfc_rbac' => [
        'vufind_permission_provider_manager' => [
            'factories' => [
                'queriedCookie' => 'ThULB\Role\PermissionProvider\Factory::getQueriedCookie',
            ]
        ],
    ],
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoute($config, 'MyResearch/ChangePasswordLink');

return $config;