<?php

namespace ThULB\Search\Facets;

use VuFind\Config\PluginManager as ConfigPluginManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Search\Base\Params;
use VuFindSearch\Backend\Solr\Response\Json\NamedList;
use Laminas\Config\Config;

class LocationFacet implements IFacet, TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    private ConfigPluginManager $configLoader;

    /**
     * Configurations for ThULB's custom facets.
     */
    private Config $thulbFacets;

    /**
     * List of all created facets.
     */
    private ?array $facetList = null;

    /**
     * List of keys and their respective internal search values.
     */
    private array $filterValueList = array();

    /**
     * Constructor
     *
     * @param ConfigPluginManager $configLoader
     */
    public function __construct(ConfigPluginManager $configLoader) {
        $this->configLoader = $configLoader;
        $this->thulbFacets = $this->configLoader->get('thulb_facets');

        // Create list with keys/internal values,
        foreach($this->thulbFacets->standort_iln_str_mv ?? [] as $description => $value) {
            $this->filterValueList[$description] = $this->getInternalValue($value);
        }
    }

    /**
     * Creates the facet list for this field.
     *
     * @param string    $field  The field of this list.
     * @param NamedList $data   The data to populate the facet list with.
     * @param Params    $params Params of the search.
     *
     * @return array
     */
    public function getFacetList(string $field, NamedList $data, Params $params) : array {
        if(!empty($this->facetList)) {
            return $this->facetList;
        }

//        $data = $this->getDataAsArray($data);
        $operator = $params->getFacetOperator($field);
        $fieldWithOperator = $operator == 'OR' ? "~$field" : $field;

        $facetList = array();

        // Create Facet list with all parents and children
        foreach ($this->thulbFacets->standort_iln_str_mv ?? [] as $description => $value) {
            $facetList[] = array(
                'internalValue' => $this->filterValueList[$description],
                'value' => $description,
                'displayText' => $this->translate("ThULBFacets::$description"),
                'count' => 0,
                'operator' => $operator,
                'isApplied' => $params->hasFilter("$fieldWithOperator:$description"),
                'children' => array(),
                'hasChildren' => false
            );

        }

        return $this->facetList = $facetList;
    }

    /**
     * Return the filter value associated with the given value.
     *
     * @param string $value Value to get the filter value for.
     *
     * @return string
     */
    public function getFilterValue(string $value) : string {
        $returnValue = $value;
        if (isset($this->filterValueList[$value])) {
            $returnValue = $this->filterValueList[$value];
        }

        return $returnValue;
    }

    /**
     * Creates the internal value of the given value.
     *
     * @param string $value
     *
     * @return string
     */
    private function getInternalValue(string $value) : string {
        return '("' . implode('" OR "', array_map(fn($value) => '31:' . $value, explode('|', $value))) . '")';
    }

    /**
     * Creates an array with value:count pairs of the given data.
     *
     * @param NamedList $data
     *
     * @return array
     */
    private function getDataAsArray(NamedList $data) : array {
        $dataArray = array();
        foreach($data as $value => $count) {
            $dataArray[$value] = $count;
        }
        return $dataArray;
    }
}