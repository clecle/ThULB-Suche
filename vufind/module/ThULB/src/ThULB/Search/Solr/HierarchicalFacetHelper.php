<?php

namespace ThULB\Search\Solr;

use VuFind\I18n\TranslatableString;
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
    protected function createFacetItem($facet, $item, $urlHelper, $escape = true, Results $results = null) : array
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

    /**
     * Format a filter string in parts suitable for displaying or translation
     *
     * @param string $filter Filter value
     *
     * @return array
     */
    public function getFilterStringParts($filter) : array
    {
        $parts = explode('/', $filter);
        if (count($parts) <= 1 || !is_numeric($parts[0])) {
            return [new TranslatableString($filter, $filter)];
        }
        $result = [];
        for ($level = 0; $level <= $parts[0]; $level++) {
            $str = $level . '/' . implode('/', array_slice($parts, 1, $level + 1))
                . '/';
            $result[] = new TranslatableString($str, $parts[$level + 1]);
        }
        return $result;
    }
}