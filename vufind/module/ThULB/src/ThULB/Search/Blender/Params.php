<?php

namespace ThULB\Search\Blender;

use ThULB\Search\Facets\PluginManager;
use VuFind\Search\Blender\Params as OriginalParams;
use VuFind\Search\Solr\HierarchicalFacetHelper;

class Params extends OriginalParams
{
    private $facetManager = null;

    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options       Options to use
     * @param \VuFind\Config\PluginManager $configLoader  Config loader
     * @param HierarchicalFacetHelper      $facetHelper   Hierarchical facet helper
     * @param array                        $searchParams  Search params for backends
     * @param \Laminas\Config\Config       $blenderConfig Blender configuration
     * @param array                        $mappings      Blender mappings
     * @param PluginManager|null           $facetManager
     */
    public function __construct(
        \VuFind\Search\Base\Options $options,
        \VuFind\Config\PluginManager $configLoader,
        HierarchicalFacetHelper $facetHelper,
        array $searchParams,
        \Laminas\Config\Config $blenderConfig,
        array $mappings,
        PluginManager $facetManager = null
    ) {

        $this->facetManager = $facetManager;

        parent::__construct(
            $options,
            $configLoader,
            $facetHelper,
            $searchParams,
            $blenderConfig,
            $mappings
        );
    }

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings() : array
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
    public function getFacetSettings() : array
    {
        $facetSet = parent::getFacetSettings();

        if (!empty($this->facetConfig)) {
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
}