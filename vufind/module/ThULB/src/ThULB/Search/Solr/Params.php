<?php

namespace ThULB\Search\Solr;

use ThULB\Search\Facets\PluginManager;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use VuFind\Search\Solr\Params as OriginalParams;

class Params extends OriginalParams
{
    private $facetManager = null;

    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options $options Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     * @param HierarchicalFacetHelper $facetHelper Hierarchical facet helper
     * @param PluginManager|null $facetManager
     */
    public function __construct($options, \VuFind\Config\PluginManager $configLoader,
                                HierarchicalFacetHelper $facetHelper = null, PluginManager $facetManager = null
    ) {
        parent::__construct($options, $configLoader, $facetHelper);
        $this->facetManager = $facetManager;
    }

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
                if ($this->facetManager && $this->facetManager->has($field)) {
                    $q = $field . ':' . $this->facetManager->get($field)->getFilterValue($value);
                } elseif ($field == '#') {
                    // Special case -- complex filter, that should be taken as-is:
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

        $config = $this->configLoader->get($this->getOptions()->getFacetsIni());
        $thbibFilter = str_replace('#:', '', $config->CheckboxFacets->Th_Biblio);

        // Look for thbib filter in active filters
        if(!empty($this->filterList)) {
            foreach($this->filterList as $filter) {
                if(in_array($thbibFilter, $filter)) {
                    $removeFilter = false;
                    break;
                }
            }
        }

        // Remove filter
        if($removeFilter) {
            $facetField = 'class_local_iln';
            if($this->getFacetOperator($facetField) == 'OR') {
                $facetField = "{!ex={$facetField}_filter}$facetField";
            }
            $index = array_keys($facetSet['field'], $facetField);
            if(is_array($index) && count($index) > 0) {
                unset($facetSet['field'][$index[0]]);
            }
        }

        return $facetSet;
    }
}