<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ThULB\Hierarchy\TreeDataSource;

use VuFind\Hierarchy\TreeDataSource\Solr as OriginalSolr;
use VuFind\Hierarchy\TreeDataFormatter\PluginManager as FormatterManager;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\ParamBag;

/**
 * VuFind\Hierarchy\TreeDataSource
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Solr extends OriginalSolr
{
    protected $maxRows;

    /**
     * Constructor.
     *
     * @param Connector        $connector Solr connector
     * @param FormatterManager $fm        Formatter manager
     * @param string           $cacheDir  Directory to hold cache results (optional)
     * @param array            $filters   Filters to apply to Solr tree queries
     */
    public function __construct(Connector $connector, FormatterManager $fm,
        $cacheDir = null, $filters = [], $maxRows = 2147483647
    ) {
        parent::__construct($connector, $fm, $cacheDir, $filters, $maxRows);
        
        $this->maxRows = $maxRows;
    }
    
    /**
     * Search Solr.
     *
     * @param string $q    Search query
     * @param int    $rows Max rows to retrieve (default = int max)
     *
     * @return array
     */
    protected function searchSolr($q, $rows = null)
    {
        if (is_null($rows)) {
            $rows = $this->maxRows;
        }
        
        $params = new ParamBag(
            [
                'q'  => [$q],
                'fq' => $this->filters,
                'hl' => ['false'],
                'fl' => ['title,id,hierarchy_parent_id,hierarchy_top_id,'
                    . 'is_hierarchy_id,hierarchy_sequence,title_in_hierarchy'],
                'wt' => ['json'],
                'json.nl' => ['arrarr'],
                'rows' => [$rows], // Integer max
                'start' => [0]
            ]
        );
        $response = $this->solrConnector->search($params);
        return json_decode($response);
    }
}
