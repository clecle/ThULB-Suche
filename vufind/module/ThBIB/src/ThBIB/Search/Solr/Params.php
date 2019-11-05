<?php

namespace ThBIB\Search\Solr;

use VuFind\Search\Solr\Params as OriginalParams;

class Params extends OriginalParams
{
    const THBIB_FILTER = 'class_local:thüringen AND class_local:de-601 AND class_local:31';

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        // Define Filter Query
        $filterQuery = [];
        $orFilters = [];
        $filterList = array_merge(
            $this->getHiddenFilters(),
            $this->filterList
        );
        foreach ($filterList as $field => $filter) {
            if ($orFacet = (substr($field, 0, 1) == '~')) {
                $field = substr($field, 1);
            }
            foreach ($filter as $value) {
                // Special case -- complex filter, that should be taken as-is:
                if ($field == '#') {
                    $q = $value;
                } elseif (substr($value, -1) == '*'
                    || preg_match('/\[[^\]]+\s+TO\s+[^\]]+\]/', $value)
                    || preg_match('/^\(.*\)$/', $value)    // do not escape when the value has parentheses
                ) {
                    // Special case -- allow trailing wildcards and ranges
                    $q = $field . ':' . $value;
                } else {
                    $q = $field . ':"' . addcslashes($value, '"\\') . '"';
                }
                if ($orFacet) {
                    $orFilters[$field] = $orFilters[$field] ?? [];
                    $orFilters[$field][] = $q;
                } else {
                    $filterQuery[] = $q;
                }
            }
        }
        foreach ($orFilters as $field => $parts) {
            $filterQuery[] = '{!tag=' . $field . '_filter}' . $field
                . ':(' . implode(' OR ', $parts) . ')';
        }
        return $filterQuery;
    }

    /**
     * Return current facet configurations
     *
     * @return array $facetSet
     */
    public function getFacetSettings()
    {
        $facetSet = parent::getFacetSettings();

        if (!empty($this->facetConfig)) {

            $facetSet = $this->checkForThbibFilter($facetSet);

            // add facet prefixes if declared
            $config = $this->configLoader->get($this->getOptions()->getFacetsIni());
            if ($config->FacetFieldPrefixes != null) {
                foreach ($config->FacetFieldPrefixes as $field => $prefix) {
                    $facetSet["f.{$field}.facet.prefix"] = $prefix;
                }
            }
        }

        return $facetSet;
    }

    /**
     * Removes the ThBIB facet from the facetSet if the ThBIB filter is not used in
     * normal Filters or RawHiddenFilters.
     *
     * @param array $facetSet
     *
     * @return array
     */
    public function checkForThbibFilter($facetSet) {

        $removeFilter = true;

        if(!empty($this->filterList)) {
            foreach($this->filterList as $filter) {
                if(in_array(self::THBIB_FILTER, $filter)) {
                    $removeFilter = false;
                    break;
                }
            }
        }

        $config = $this->configLoader->get($this->getOptions()->getSearchIni());
        if ($config->RawHiddenFilters != null) {
            foreach ($config->RawHiddenFilters as $rawHiddenFilter) {
                if($rawHiddenFilter == self::THBIB_FILTER) {
                    $removeFilter = false;
                    break;
                }
            }
        }

        if($removeFilter) {
            $index = array_keys($facetSet['field'], '{!ex=class_local_iln_filter}class_local_iln');
            if(is_array($index) && count($index) > 0) {
                unset($facetSet['field'][$index[0]]);
            }
        }

        return $facetSet;
    }
}