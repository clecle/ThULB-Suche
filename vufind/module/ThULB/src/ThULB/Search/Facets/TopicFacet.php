<?php

namespace ThULB\Search\Facets;

use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Search\Base\Params;
use Laminas\Config\Config;

class TopicFacet implements IFacet, TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    private \VuFind\Config\PluginManager $configLoader;

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
     * @param \VuFind\Config\PluginManager $configLoader
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader) {

    }

    /**
     * Creates the facet list for this field.
     *
     * @param string $field  The field of this list.
     * @param array  $data   The data to populate the facet list with.
     * @param Params $params Params of the search.
     *
     * @return array
     */
    public function getFacetList(string $field, array $data, Params $params) : array {
        if($this->facetList ?? false) {
            return $this->facetList;
        }

        $operator = $params->getFacetOperator($field);
        $fieldWithOperator = $operator == 'OR' ? "~$field" : $field;

        $facetList = array();

        // Create Facet list with all parents and children
        foreach ($data as $facetValue => $facetCount) {
            if(!preg_match('/[0-9]{2}\./', $facetValue)) {
                continue;
            }

            $parentValue = substr($facetValue, 0, 2);

            if(!($facetList[$parentValue] ?? false)) {
                $facetList[$parentValue] = array(
                    'internalValue' => $parentValue,
                    'value' => $parentValue,
                    'displayText' => $this->translate("BKL::$parentValue"),
                    'count' => 0,
                    'operator' => $operator,
                    'isApplied' => $params->hasFilter("$fieldWithOperator:$parentValue"),
                    'children' => array(),
                    'hasChildren' => false
                );
            }

            $facetList[$parentValue]['count'] += $facetCount;
        }

        usort($facetList, function ($facet1, $facet2) {
           if($facet1['count'] == $facet2['count']) {
               return strcmp($facet1['displayText'], $facet2['displayText']);
           }
           return $facet1['count'] > $facet2['count'] ? -1 : 1;
        });

        return $this->facetList = $facetList;
    }

    public function getFilterValue(string $value): string
    {
        return $value . '*';
    }
}