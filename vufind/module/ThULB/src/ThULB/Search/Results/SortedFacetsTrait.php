<?php
/**
 * Trait for result model classes, where facet lists get sorted.
 *
 * PHP version 5
 *
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
 *
 */

namespace ThULB\Search\Results;

/**
 * A trait to override the function to get a facet list with a version that
 * additionally sorts the facets
 */
trait SortedFacetsTrait
{
    /**
     * Returns the stored list of faceSortedFacetsTraits for the last search with all applied
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
        return $this->sortFacets($facetList);
    }

    /**
     * Sorts the facet fields of a list of facets to put all applied facets on top.
     * All the other sorting stays the same.
     *
     * @param array $facetList Facets data arrays
     *
     * @return array Facets data arrays
     */
    public function sortFacets($facetList) {
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
     * @param array $facetFields array of facet fields
     *
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
