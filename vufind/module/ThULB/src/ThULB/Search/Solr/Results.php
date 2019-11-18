<?php
/**
 * Solr aspect of the Search Multi-class (Results)
 *
 * PHP version 5
 *
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\Search\Solr;

use ThULB\Search\Results\SortedFacetsTrait;
use VuFind\Search\Solr\Results as OriginalResults;
use Zend\Config\Config;

/**
 * Results
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Results extends OriginalResults
{
    use SortedFacetsTrait;

    /**
     * Returns the stored list of facets for the last search
     *
     * @param array $filter Array of field => on-screen description listing
     * all of the desired facet fields; set to null to get all configured values.
     *
     * @return array Facets data arrays
     */
    public function getFacetList($filter = null)
    {
        $list = parent::getFacetList($filter);

        if (empty($list) || !($facetFieldPrefixes = $this->getOptions()->getFacetPrefixes())) {
            return $list;
        }

        // replace normal ThBIB facet list with hierarchical list
        if(isset($list['class_local_iln'])) {
            $list['class_local_iln']['list'] = $this->getTBHierarchies($list['class_local_iln']['list']);
        }

        // format display text
        foreach ($facetFieldPrefixes as $field => $prefix) {
            if (!array_key_exists($field, $list)) {
                continue;
            }
            $replace = array(
                $facetFieldPrefixes[$field],
                '&lt;Thüringen&gt;'
            );
            foreach ($list[$field]['list'] as $index => $item) {
                $list[$field]['list'][$index]['displayText'] =
                    str_replace($replace, '', $item['displayText']);
            }
        }

        return $list;
    }

    /**
     * Creates the "Thüringen Bibliographie" hierarchical facet.
     *
     * @param array $oldFacetList
     *
     * @return array
     */
    public function getTBHierarchies($oldFacetList) {

        $groups = $this->getOptions()->getTBClassificationGroups();
        $classifications = $this->getOptions()->getTBClassification();

        if(empty($groups) || empty($classifications)) {
            return $oldFacetList;
        }

        $groupFacetList = $this->getTBGroupFacetList($groups, $classifications);
        $classificationList = $this->getTBClassificationList($classifications, $groups);
        unset($groups);
        unset($classifications);

        // create multidimensional array with parent > child structure
        foreach($oldFacetList as $index => $oldEntry) {
            $displayText = $oldEntry['displayText'];
            if(!isset($classificationList[$displayText])) {
                continue;
            }

            $group = $classificationList[$displayText];
            $oldEntry['parent'] = $group;
            $oldEntry['displayText'] = htmlspecialchars($displayText);
            $groupFacetList[$group]['count'] += $oldEntry['count'];
            $groupFacetList[$group]['children'][] = $oldEntry;
        }
        unset($oldFacetList);

        sort($groupFacetList);

        // create an array with parents and children
        $newFacetList = array();
        foreach($groupFacetList as $group) {
            if ($group['count'] == 0) {
                continue;
            }
            $children = $group['children'];
            unset($group['children']);
            usort($children, array($this, 'compareTBFacets'));

            $newFacetList[] = $group;
            $newFacetList = array_merge($newFacetList, $children);
        }

        return $newFacetList;
    }

    /**
     * Creates an array of facets for the given groups.
     * Keys are the names of the groups.
     *
     * @param Config $groups List of strings with the names for the group facets.
     * @param Config $classifications List of tb classifications
     *
     * @return array
     */
    protected function getTBGroupFacetList($groups, $classifications) {

        $groupList = array();
        foreach($groups as $groupShort => $group) {

            $queryParts = array();
            foreach ($classifications[$groupShort] as $classification) {
                $queryParts[] = '"31:' . $classification . '"';
            }

            $groupList[$group] = array(
                'value' => $group,
                'displayText' => $group,
                'count' => 0,
                'operator' => 'OR',
                'isApplied' => false,
                'children' => array(),
                'tb_facet_value' => '~class_local_iln:(' . implode(' OR ', $queryParts) . ')'
            );
        }

        return $groupList;
    }

    /**
     * Creates an array with all available classifications as keys and their respective groups as values.
     *
     * @param Config $classifications
     * @param Config $groups
     *
     * @return array
     */
    protected function getTBClassificationList($classifications, $groups) {
        $classificationList = array();
        foreach($classifications as $group => $items) {
            foreach ($items as $item) {
                if (isset($groups[$group])) {
                    $classificationList['31:' . $item] = $groups[$group];
                }
            }
        }
        return $classificationList;
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
    public static function compareTBFacets($facet1, $facet2)
    {
        if($facet1['count'] == $facet2['count']) {
            return strcmp($facet1['displayText'], $facet2['displayText']);
        }
        return $facet2['count'] - $facet1['count'];
    }
}
