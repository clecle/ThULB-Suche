<?php
/**
 * Solr aspect of the Search Multi-class (Results)
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

namespace ThULB\Search\Solr;

use ThULB\Search\Facets\PluginManager;
use ThULB\Search\Results\SortedFacetsTrait;
use VuFind\Record\Loader;
use VuFind\Search\Base\Params;
use VuFind\Search\Solr\Results as OriginalResults;
use VuFindSearch\Backend\Solr\Response\Json\Facets;
use VuFindSearch\Service as SearchService;

/**
 * Results
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Results extends OriginalResults
{
    use SortedFacetsTrait {
        getFacetList as public trait_getFacetList;
    }

    /* @var Facets */
    protected $responseFacets;

    /**
     * Facet PluginManager.
     *
     * @var PluginManager
     */
    private $facetManager;

    public function __construct(Params $params, SearchService $searchService,
                                Loader $recordLoader, PluginManager $facetManager)
    {
        $this->facetManager = $facetManager;
        parent::__construct($params, $searchService, $recordLoader);
    }

    /**
     * Returns the stored list of facets for the last search
     *
     * @param array $filter Array of field => on-screen description listing
     *                      all the desired facet fields; set to null to
     *                      get all configured values.
     *
     * @return array        Facets data arrays
     */
    public function getFacetList($filter = null) : array
    {
        // Make sure we have processed the search before proceeding:
        if (null === $this->responseFacets) {
            $this->performAndProcessSearch();
        }

        // If there is no filter, we'll use all facets as the filter:
        if (null === $filter) {
            $filter = $this->getParams()->getFacetConfig();
        }

        // Start building the facet list:
        $list = [];

        // Loop through every field returned by the result set
        $fieldFacets = $this->responseFacets->getFieldFacets();
        $translatedFacets = $this->getOptions()->getTranslatedFacets();
        $hierarchicalFacets = $this->getOptions()->getHierarchicalFacets();
        foreach (array_keys($filter) as $field) {
            $data = $fieldFacets[$field] ?? [];
            // Skip empty arrays:
            if (count($data) < 1) {
                continue;
            }
            // Initialize the settings for the current field
            $list[$field] = [];
            // Add the on-screen label
            $list[$field]['label'] = $filter[$field];
            // Build our array of values for this field
            $list[$field]['list']  = [];
            // Should we translate values for the current facet?
            $translate = in_array($field, $translatedFacets);
            $hierarchical = in_array($field, $hierarchicalFacets);

            // Use custom facet class if available
            if($this->facetManager->has($field)) {
                $facet = $this->facetManager->get($field);
                $list[$field]['list'] =
                    $facet->getFacetList($field, $data, $this->getParams());

                continue;
            }

            // Loop through values:
            foreach ($data as $value => $count) {
                // Initialize the array of data about the current facet:
                $currentSettings = compact('value', 'count');

                $displayText = $this->getParams()
                    ->getFacetValueRawDisplayText($field, $value);

                if ($hierarchical) {
                    if (!$this->hierarchicalFacetHelper) {
                        throw new \Exception(
                            get_class($this)
                            . ': hierarchical facet helper unavailable'
                        );
                    }
                    $displayText = $this->hierarchicalFacetHelper
                        ->formatDisplayText($displayText);
                }

                $currentSettings['displayText'] = $translate
                    ? $this->getParams()->translateFacetValue($field, $displayText)
                    : $displayText;
                $currentSettings['operator']
                    = $this->getParams()->getFacetOperator($field);
                $currentSettings['isApplied']
                    = $this->getParams()->hasFilter("$field:" . $value)
                    || $this->getParams()->hasFilter("~$field:" . $value);

                // Store the collected values:
                $list[$field]['list'][] = $currentSettings;
            }
        }

        return $this->sortFacets($list);
    }
}
