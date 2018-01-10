<?php

namespace ThULB\Search\Summon;
use VuFind\Search\Summon\Results as OriginalResults;

/**
 * Description of Params
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Results extends OriginalResults
{
    use \ThULB\Search\Results\SortedFacetsTrait;
    
    /**
     * Get complete facet counts for several index fields. Fork of the original
     * function in VuFind\Search\Summon\Results with ored param
     *
     * @param array  $facetfields  name of the Solr fields to return facets for
     * @param bool   $removeFilter Clear existing filters from selected fields (true)
     * or retain them (false)?
     * @param int    $limit        A limit for the number of facets returned, this
     * may be useful for very large amounts of facets that can break the JSON parse
     * method because of PHP out of memory exceptions (default = -1, no limit).
     * @param string $facetSort    A facet sort value to use (null to retain current)
     * @param int    $page         1 based. Offsets results by limit.
     *
     * @return array an array with the facet values for each index field
     */
    public function getPartialFieldFacets($facetfields, $removeFilter = true,
        $limit = -1, $facetSort = null, $page = null, $ored = false
    ) {
        $params = $this->getParams();
        $query  = $params->getQuery();
        // No limit not implemented with Summon: cause page loop
        if ($limit == -1) {
            if ($page === null) {
                $page = 1;
            }
            $limit = 50;
        }
        $params->resetFacetConfig();
        if (null !== $facetSort && 'count' !== $facetSort) {
            throw new \Exception("$facetSort facet sort not supported by Summon.");
        }
        foreach ($facetfields as $facet) {
            $params->addFacet($facet, null, $ored);

            // Clear existing filters for the selected field if necessary:
            if ($removeFilter) {
                $params->removeAllFilters($facet);
            }
        }
        $params = $params->getBackendParameters();
        
        // FIX: manipulate params to use lightbox limit instead of standard
        //      facet limit in the current context
        $facetSetting = $params->get('facets');
        if ($facetSetting) {
            $facetSetting[0] = preg_replace('/[\d]+$/', $limit, $facetSetting[0]);
            $params->set('facets', $facetSetting);
        }
        
        $collection = $this->getSearchService()->search(
            'Summon', $query, 0, 0, $params
        );

        $facets = $collection->getFacets();
        if (isset($facets[0]) && $facets[0]['counts']) {
            $this->sortFacetList($facets[0]['counts']);
        }
        $ret = [];
        foreach ($facets as $data) {
            if (in_array($data['displayName'], $facetfields)) {
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
}
