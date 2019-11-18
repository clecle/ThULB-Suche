<?php

namespace ThBIB\Search\Solr;

use VuFind\Search\Solr\Options as OriginalOptions;
use Zend\Config\Config;

/**
 * Created to enable VuFind to find the Options when using ThULB\Search\Solr\Params
 */
class Options extends OriginalOptions
{
    /**
     * Returns the FacetPrefixes from the config file facets.ini.
     *
     * @return Config Prefix data array
     */
    public function getFacetPrefixes() {
        return $this->configLoader->get($this->getFacetsIni())->FacetFieldPrefixes;
    }

    /**
     * Returns the classification groups of the "Thüringen Bibliographie" from the config file TBClassification.ini.
     *
     * @return Config Classification group data array
     */
    public function getTBClassificationGroups() {
        return $this->configLoader->get("TBClassification")->TB_Classification_Groups;
    }

    /**
     * Returns the classification of the "Thüringen Bibliographie" from the config file TBClassification.ini.
     *
     * @return Config Classification data array
     */
    public function getTBClassification() {
        return $this->configLoader->get("TBClassification")->TB_Classification;
    }
}