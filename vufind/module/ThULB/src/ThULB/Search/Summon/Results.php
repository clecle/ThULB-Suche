<?php
/**
 * Summon Search Results
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace ThULB\Search\Summon;
use Exception;
use ThULB\Search\Results\SortedFacetsTrait;
use VuFind\Search\Summon\Params;
use VuFind\Search\Summon\Results as OriginalResults;
use VuFindSearch\Command\SearchCommand;

/**
 * Params
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Results extends OriginalResults
{
    use SortedFacetsTrait;

    /**
     * Get complete facet counts for several index fields. Fork of the original
     * function in VuFind\Search\Summon\Results with ored param
     *
     * @param array  $facetFields  name of the Solr fields to return facets for
     * @param bool   $removeFilter Clear existing filters from selected fields (true)
     *                             or retain them (false)?
     * @param int    $limit        A limit for the number of facets returned, this may be
     *                             useful for very large amounts of facets that can break
     *                             the JSON parse method because of PHP out of memory
     *                             exceptions (default = -1, no limit).
     * @param string $facetSort    A facet sort value to use (null to retain current)
     * @param int    $page         1 based. Offsets results by limit.
     * @param bool   $isOrFacet
     *
     * @return array an array with the facet values for each index field
     *
     * @throws Exception
     */
    public function getPartialFieldFacets($facetFields, $removeFilter = true,
                                          $limit = -1, $facetSort = null, $page = null, bool $isOrFacet = false
    ) : array {
        // set parameters will be used in getFacetList's 'performAndProcessSearch' call
        /* @var $params Params */
        $params = $this->getParams();
        // No limit not implemented with Summon: cause page loop
        if ($limit == -1) {
            if ($page === null) {
                $page = 1;
            }
            $limit = 50;
        }
        $params->resetFacetConfig();
        foreach ($facetFields as $facet) {
            $mode = $params->getFacetOperator($facet) === 'OR' ? 'or' : 'and';
            $params->addFacet("$facet,$mode,$page,$limit");

            // Clear existing filters for the selected field if necessary:
            if ($removeFilter) {
                $params->removeAllFilters($facet);
            }
        }

        $facets = $this->getFacetList();
        if (isset($facets[0]) && $facets[0]['counts']) {
            $this->sortFacetList($facets[0]['counts']);
        }
        $ret = [];
        foreach ($facets as $data) {
            if (in_array($data['displayName'], $facetFields)) {
                $formatted = $this->formatFacetData($data);
                $list = $formatted['counts'];
                $ret[$data['displayName']] = [
                    'data' => [
                        'label' => $data['displayName'],
                        'list' => $list,
                    ],
                    'more' => null
                ];
            }
        }

        // Send back data:
        return $ret;
    }

    /**
     * Process spelling suggestions from the results object
     *
     * @param array $spelling Suggestions from Summon
     *
     * @return void
     */
    protected function processSpelling($spelling) : void
    {
        $this->suggestions = [];
        foreach ($spelling as $current) {
            if(!isset($current['originalQuery']) && isset($current['suggestion'])) {
                $current = $current['suggestion'];
            }
            if (!isset($this->suggestions[$current['originalQuery']])) {
                $this->suggestions[$current['originalQuery']] = [
                    'suggestions' => []
                ];
            }
            $this->suggestions[$current['originalQuery']]['suggestions'][]
                = $current['suggestedQuery'];
        }
    }

    public function getFacetList($filter = null) {
        if (null === $this->responseFacets) {
            $this->performAndProcessSearch();
        }

        // change special facets to work with the buildFacetList method
        foreach($this->responseFacets as $key => $facet) {
            if(is_numeric($key)) {
                $this->responseFacets[$facet['displayName']] = array (
                    $facet['displayName'] => 1
                );
                unset($this->responseFacets[$key]);
            }
        }

        $facetList = $this->buildFacetList($this->responseFacets, $filter);

        // add fields to make it compatible with 'getPartialFieldFacets' method
        foreach($facetList as $key => $data) {
            $facetList[$key]['displayName'] = $data['label'];
            $facetList[$key]['list'] = array_map(function ($item) {
                $item['displayName'] = $item['displayText'];
                $item['isNegated'] = false;
                return $item;
            }, $data['list']);
        }
        return $this->sortFacets($facetList);
    }
}
