<?php

namespace ThULB\Recommend;

use Exception;
use ThULB\Search\Solr\HierarchicalFacetHelper;
use VuFind\Recommend\SideFacets as OriginalSideFacets;

class SideFacets extends OriginalSideFacets
{
    /**
     * Hierarchical facet helper
     *
     * @var HierarchicalFacetHelper
     */
    protected $hierarchicalFacetHelper;

    /**
     * Get facet information from the search results.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getFacetSet() : array
    {
        $facetSet = $this->results->getFacetList($this->mainFacets);

        foreach ($this->hierarchicalFacets as $hierarchicalFacet) {
            if (isset($facetSet[$hierarchicalFacet])) {
                if (!$this->hierarchicalFacetHelper) {
                    throw new Exception(
                        get_class($this) . ': hierarchical facet helper unavailable'
                    );
                }

                // use ThBIB helper
                $facetSet[$hierarchicalFacet]['list'] = $this->hierarchicalFacetHelper->buildFacetArray(
                    $hierarchicalFacet, $facetSet[$hierarchicalFacet]['list'],
                    $this->results->getUrlQuery(), true, $this->results
                );

                $facetSet[$hierarchicalFacet]['list'] = $this->hierarchicalFacetHelper->filterFacets(
                    $hierarchicalFacet,
                    $facetSet[$hierarchicalFacet]['list'],
                    $this->results->getOptions()
                );
            }
        }

        $configFile = $this->results->getBackendId() == 'Solr' || $this->results->getBackendId() == null
            ? 'facets' : $this->results->getBackendId();
        $facetsSortedByIndex =
            $this->configLoader->get($configFile)->Results_Settings->sorted_by_index?->toArray() ?? [];

        foreach ($facetsSortedByIndex as $facet) {
            if(!empty($facetSet[$facet]['list'])) {
                usort($facetSet[$facet]['list'], function ($facet1, $facet2) {
                    return strcmp($facet1['displayText'], $facet2['displayText']);
                });
            }
        }

        return $facetSet;
    }
}