<?php

namespace ThBIB\Search\Solr;

use VuFind\Search\Solr\Params as OriginalParams;

class Params extends OriginalParams
{
//    const THBIB_FILTER = 'collection_details:\"GBV_ILN_31\" AND class_local:"thüringen" AND class_local:"de-601';
    const THBIB_FILTER = 'class_local:thüringen AND class_local:de-601';

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

            $config = $this->configLoader->get($this->getOptions()->getFacetsIni());
            if ($config->FacetFieldPrefixes != null) {
                foreach ($config->FacetFieldPrefixes as $field => $prefix) {
                    $facetSet["f.{$field}.facet.prefix"] = $prefix;
                }
            }
        }

        return $facetSet;
    }

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
            $index = array_keys($facetSet['field'], 'class_local_iln');
            if(is_array($index) && count($index) > 0) {
                unset($facetSet['field'][$index[0]]);
            }
        }

        return $facetSet;
    }
}