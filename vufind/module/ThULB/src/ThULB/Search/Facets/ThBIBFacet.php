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
        $this->configLoader = $configLoader;
        $this->thulbFacets = $this->configLoader->get('thulb_facets');

        // Create list with keys/internal values,
        foreach($this->thulbFacets->TB_Classification_Groups as $groupKey => $groupValue) {
            $this->filterValueList[$groupKey] = $this->getGroupInternalValue($groupKey);
            foreach($this->thulbFacets->$groupKey ?? [] as $child) {
                $this->filterValueList[$child] = $this->getChildInternalValue($child);
            }
        }
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
        if(!empty($this->facetList)) {
            return $this->facetList;
        }

        $operator = $params->getFacetOperator($field);
        $fieldWithOperator = $operator == 'OR' ? "~$field" : $field;

        $facetList = array();

        // Create Facet list with all parents and children
        foreach ($this->thulbFacets->TB_Classification_Groups as $groupKey => $groupValue) {
            $parentFacet = array(
                'internalValue' => $this->filterValueList[$groupKey],
                'value' => $groupKey,
                'displayText' => $this->translate("ThULBFacets::$groupKey"),
                'count' => 0,
                'operator' => $operator,
                'isApplied' => $params->hasFilter("$fieldWithOperator:$groupKey"),
                'children' => array(),
                'hasChildren' => false
            );

            // Always show facet groups with defined values
            if(isset($this->thulbFacets->TB_Group_Values->$groupKey)) {
                $parentFacet['count'] = 1;
            }

            // Create children facets
            $childFacetList = array();
            foreach ($this->thulbFacets->$groupKey ?? [] as $child) {
                $internalValue = $this->filterValueList[$child];
                if(!isset($data[$internalValue]) || $data[$internalValue] < 1) {
                    continue;
                }

                $childFacetList[] = array(
                    'internalValue' => $internalValue,
                    'value' => $child,
                    'displayText' => $this->translate("ThULBFacets::$child"),
                    'count' => $data[$internalValue],
                    'operator' => $operator,
                    'isApplied' => $params->hasFilter("$fieldWithOperator:$child"),
                    'parent' => $groupKey
                );

                $parentFacet['count'] += $data[$internalValue];
                $parentFacet['hasChildren'] = true;
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
    public function getFilterValue(string $value) : string {
        $returnValue = $value;
        if (isset($this->filterValueList[$value]) && $this->thulbFacets) {
            $returnValue = $this->filterValueList[$value];
            if(!isset($this->thulbFacets->TB_Classification_Groups[$value])) {
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
    private function getChildInternalValue(string $child) : string {
        return "31:$child <Thüringen>";
    }

    /**
     * Creates the internal value of the given group.
     *
     * @param string $group
     *
     * @return string
     */
    private function getGroupInternalValue(string $group) : string {
        if ($this->thulbFacets->TB_Group_Values->$group ?? false) {
            return $this->thulbFacets->TB_Group_Values->$group;
        }

        if ($this->thulbFacets->$group ?? false) {
            $queryParts = array();
            foreach ($this->thulbFacets->$group ?? [] as $child) {
                $queryParts[] = $this->getChildInternalValue($child);
            }
            return '("' . implode('" OR "', $queryParts) . '")';
        }

        return $group;
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
    public static function compareTBChildFacets(array $facet1, array $facet2) : int
    {
        if($facet1['count'] == $facet2['count']) {
            return strcmp($facet1['displayText'], $facet2['displayText']);
        }
        return $facet2['count'] - $facet1['count'];
    }
}