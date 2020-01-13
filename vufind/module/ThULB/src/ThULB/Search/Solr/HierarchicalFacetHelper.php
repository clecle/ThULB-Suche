<?php

namespace ThULB\Search\Solr;

use VuFind\Search\UrlQueryHelper;
use VuFind\Search\Solr\HierarchicalFacetHelper as OriginalFacetHelper;

class HierarchicalFacetHelper extends OriginalFacetHelper
{
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

        return $item;
    }
}