<?php
/**
 * Override of the VuFind Hierarchy Tree Data Source (Solr)
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @package  HierarchyTree_DataSource
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 * 
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
     * @param int              $maxRows
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
    protected function searchSolr($q, $rows = null) : array
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
