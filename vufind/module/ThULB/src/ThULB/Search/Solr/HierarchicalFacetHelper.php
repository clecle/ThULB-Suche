<?php

namespace ThULB\Search\Solr;

use VuFind\Search\UrlQueryHelper;
use VuFind\Search\Solr\HierarchicalFacetHelper as OriginalFacetHelper;

class HierarchicalFacetHelper extends OriginalFacetHelper
{
    /**
     * Helper method for building hierarchical facets:
     * Convert facet list to a hierarchical array
     *
     * @param string              $facet     Facet name
     * @param array               $facetList Facet list
     * @param UrlQueryHelper|bool $urlHelper Query URL helper for building facet URLs
     * @param bool                $escape    Whether to escape URLs
     * @param Results|null        $results   Result object from the search
     *
     * @return array Facet hierarchy
     */
    public function buildFacetArray($facet, $facetList, $urlHelper = false,
                                    $escape = true, $results = null
    ) {
        // Create a keyed (for conversion to hierarchical) array of facet data
        $keyedList = [];
        foreach ($facetList as $item) {
            $keyedList[$item['value']] = $this->createFacetItem(
                $facet, $item, $urlHelper, $escape, $results
            );
        }

        // Convert the keyed array to a hierarchical array
        $result = [];
        foreach ($keyedList as &$item) {
            if ($item['level'] > 0) {
                $keyedList[$item['parent']]['children'][] = &$item;
            } else {
                $result[] = &$item;
            }
        }

        // Update information on whether items have applied children
        $this->updateAppliedChildrenStatus($result);

        return $result;
    }

    /**
     * Create an item for the hierarchical facet array
     *
     * @param string         $facet     Facet name
     * @param array          $item      Facet item received from Solr
     * @param UrlQueryHelper $urlHelper UrlQueryHelper for creating facet URLs
     * @param bool           $escape    Whether to escape URLs
     * @param Results|null   $results   Result object from the search
     *
     * @return array Facet item
     */
    protected function createFacetItem($facet, $item, $urlHelper, $escape = true, $results = null)
    {
        $href = '';
        $exclude = '';
        // Build URLs only if we were given an URL helper
        if ($urlHelper !== false) {
            if ($item['isApplied']) {
                $href = $urlHelper->removeFacet(
                    $facet, $item['value'], $item['operator']
                )->getParams($escape);
            } else {
                $href = $urlHelper->addFacet(
                    $facet, $item['value'], $item['operator']
                )->getParams($escape);
            }
            $exclude = $urlHelper->addFacet($facet, $item['value'], 'NOT')
                ->getParams($escape);
        }

        $displayText = $item['displayText'];
        if ($displayText == $item['value']) {
            // Only show the current level part
            $displayText = $this->formatDisplayText($displayText)
                ->getDisplayString();
        }

        // has a parent? set in Results::getTBHierarchies
        $level = isset($item['parent']) ? 1 : 0;
        $parent = $level ? $item['parent'] : null;

        $item['level'] = $level;
        $item['parent'] = $parent;
        $item['displayText'] = $displayText;
        // hasAppliedChildren is updated in updateAppliedChildrenStatus
        $item['hasAppliedChildren'] = false;
        $item['href'] = $href;
        $item['exclude'] = $exclude;
        $item['children'] = [];

        $item = $this->formatTBFacet($item, $urlHelper, $results);

        return $item;
    }

    /**
     * Updates parent facet data. Updates the fields "isApplied", "value", "href"
     *
     * @param array          $facet     Facet to format.
     * @param UrlQueryHelper $urlHelper UrlQueryHelper for creating facet URLs
     * @param Results        $results   Result object from the search
     *
     * @return array
     */
    public function formatTBFacet($facet, $urlHelper, $results)
    {
        // is parent facet?
        if (!isset($facet['tb_facet_value']) || empty($facet['tb_facet_value'])) {
            return $facet;
        }

        $escape = false;

        $parentFacet['value'] = $facet['tb_facet_value'];
        $isApplied = $results->getParams()->hasFilter($parentFacet['value']);

        if ($isApplied) {
            $href = $urlHelper->removeFilter($parentFacet['value'])->getParams($escape);
        } else {
            $href = $urlHelper->addFilter($parentFacet['value'])->getParams($escape);
        }
        $facet['href'] = $href;
        $facet['isApplied'] = $isApplied;

        return $facet;
    }
}