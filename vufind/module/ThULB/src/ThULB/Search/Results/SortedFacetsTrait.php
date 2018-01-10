<?php

namespace ThULB\Search\Results;

/**
 * A trait to override the function to get a facet list with a version that
 * addiotinally sorts the facets
 */
trait SortedFacetsTrait
{
    /**
     * Returns the stored list of facets for the last search with all applied
     * facet fields first.
     *
     * @param array $filter Array of field => on-screen description listing
     * all of the desired facet fields; set to null to get all configured values.
     *
     * @return array        Facets data arrays
     */
    public function getFacetList($filter = null)
    {
        $facetList = parent::getFacetList($filter);
        
        foreach ($facetList as $facetLabel => $facetData) {
            $this->sortFacetList($facetData['list']);
            $facetData['counts'] = array_values($facetData['list']);
            $facetData['list'] = $facetData['counts'];
            $facetList[$facetLabel] = $facetData;
        }
        
        return $facetList;
    }
    
    /**
     * Sorts an array of facet fields to put all applied facets on top. All the
     * other sorting stays the same.
     * 
     * @param type $facetFields array of facet fields
     * @return boolean true
     */
    protected function sortFacetList(&$facetFields)
    {
        $facetFields = array_values($facetFields);
        // find all applied facet fields
        $appliedFields = [];
        foreach ($facetFields as $i => $facetField) {
            if ($facetField['isApplied']) {
                $facetField['fieldIndex'] = $i;
                $appliedFields[] = $facetField;
            }
        }

        // move all applied facet fields on top of their respective facet lists
        $movedFacets = 0;
        foreach (array_reverse($appliedFields) as $field) {
            unset($facetFields[$field['fieldIndex'] + $movedFacets]);
            array_unshift($facetFields, $field);
            $movedFacets++;
        }
        
        return true;
    }
}
