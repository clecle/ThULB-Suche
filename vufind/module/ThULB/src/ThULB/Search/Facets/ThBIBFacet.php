<?php

namespace ThULB\Search\Facets;

use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Search\Base\Params;
use VuFindSearch\Backend\Solr\Response\Json\NamedList;
use Laminas\Config\Config;

class ThBIBFacet implements IFacet, TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * @var \VuFind\Config\PluginManager
     */
    private $configLoader;

    /**
     * Configuration of the "Thüringen-Bibliographie".
     *
     * @var Config
     */
    private $tbClassification;

    /**
     * List of all created facets.
     *
     * @var null|array
     */
    private $facetList = null;

    /**
     * List of keys and their respective internal search values.
     *
     * @var array
     */
    private $filterValueList = array();

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader
     */
    public function __construct($configLoader) {
        $this->configLoader = $configLoader;
        $this->tbClassification = $this->configLoader->get('TBClassification');

        if ($this->tbClassification) {
            // Create list with keys/internal values,
            foreach($this->tbClassification->TB_Classification_Groups as $groupKey => $groupValue) {
                $this->filterValueList[$groupKey] = $this->getGroupInternalValue($groupKey);
                foreach($this->tbClassification->$groupKey as $child) {
                    $this->filterValueList[$child] = $this->getChildInternalValue($child);
                }
            }
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
    public function getFacetList($field, $data, $params) {
        if($this->facetList != null && !empty($this->facetList)) {
            return $this->facetList;
        }

        if (!$this->tbClassification) {
            return $this->facetList = [];
        }

        $data = $this->getDataAsArray($data);
        $operator = $params->getFacetOperator($field);
        $fieldWithOperator = $operator == 'OR' ? "~$field" : $field;

        $facetList = array();

        // Create Facet list with all parents and children
        foreach ($this->tbClassification->TB_Classification_Groups as $groupKey => $groupValue) {
            $parentFacet = array(
                'internalValue' => $this->filterValueList[$groupKey],
                'value' => $groupKey,
                'displayText' => $this->translate("ClassLocalILN::$groupKey"),
                'count' => 0,
                'operator' => $operator,
                'isApplied' => $params->hasFilter("$fieldWithOperator:$groupKey"),
                'children' => array()
            );

            // Create children facets
            $childFacetList = array();
            foreach ($this->tbClassification->$groupKey as $child) {
                $internalValue = $this->filterValueList[$child];
                if(!isset($data[$internalValue]) || $data[$internalValue] < 1) {
                    continue;
                }

                $childFacetList[] = array(
                    'internalValue' => $internalValue,
                    'value' => $child,
                    'displayText' => $this->translate("ClassLocalILN::$child"),
                    'count' => $data[$internalValue],
                    'operator' => $operator,
                    'isApplied' => $params->hasFilter("$fieldWithOperator:$child"),
                    'parent' => $groupKey
                );

                $parentFacet['count'] += $data[$internalValue];
            }
            usort($childFacetList, [$this, 'compareTBChildFacets']);

            if ($parentFacet['count'] < 1) {
                continue;
            }

            $facetList = array_merge($facetList, [$parentFacet], $childFacetList);
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
    public function getFilterValue($value) {
        $returnValue = $value;
        if (isset($this->filterValueList[$value]) && $this->tbClassification) {
            $returnValue = $this->filterValueList[$value];
            if(!isset($this->tbClassification->TB_Classification_Groups[$value])) {
                return "\"$returnValue\"";
            }
        }

        return $returnValue;
    }

    /**
     * Creates the internal value of the given child.
     *
     * @param string $child
     *
     * @return string
     */
    private function getChildInternalValue($child) {
        return "31:$child <Thüringen>";
    }

    /**
     * Creates the internal value of the given group.
     *
     * @param string $group
     *
     * @return string
     */
    private function getGroupInternalValue($group) {
        if ($this->tbClassification) {
            $queryParts = array();
            foreach($this->tbClassification->$group as $child) {
                $queryParts[] = $this->getChildInternalValue($child);
            }
            return '("' . implode('" OR "', $queryParts) . '")';
        }

        return $group;
    }

    /**
     * Creates an array with value:count pairs of the given data.
     *
     * @param NamedList $data
     *
     * @return array
     */
    private function getDataAsArray($data) {
        $dataArray = array();
        foreach($data as $value => $count) {
            $dataArray[$value] = $count;
        }
        return $dataArray;
    }

    /**
     * Compares 2 facets for sorting.
     * Sorts first by count(DESC) and then by displayText(ASC).
     *
     * @param array $facet1
     * @param array $facet2
     *
     * @return int
     */
    public static function compareTBChildFacets($facet1, $facet2)
    {
        if($facet1['count'] == $facet2['count']) {
            return strcmp($facet1['displayText'], $facet2['displayText']);
        }
        return $facet2['count'] - $facet1['count'];
    }
}