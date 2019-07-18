<?php

namespace ThBIB\AjaxHandler;

use VuFind\AjaxHandler\GetFacetData as OriginalGetFacetData;
use ThBIB\Search\Solr\Results;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Stdlib\Parameters;

class GetFacetData extends OriginalGetFacetData
{
    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     *
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $facet = $params->fromQuery('facetName');
        $sort = $params->fromQuery('facetSort');
        $operator = $params->fromQuery('facetOperator');
        $backend = $params->fromQuery('source', DEFAULT_SEARCH_BACKEND);

        $results = $this->resultsManager->get($backend);
        $paramsObj = $results->getParams();
        $paramsObj->addFacet($facet, null, $operator === 'OR');
        $paramsObj->initFromRequest(new Parameters($params->fromQuery()));

        $facets = $results->getFullFieldFacets([$facet], false, -1, 'count');
        if (empty($facets[$facet]['data']['list'])) {
            $facets = [];
        } else {
            $facetList = $facets[$facet]['data']['list'];

            if (!empty($sort)) {
                $this->facetHelper->sortFacetList($facetList, $sort == 'top');
            }

            $facets = $this->facetHelper->buildFacetArray(
                $facet, $facetList, $results->getUrlQuery(), false
            );
        }

        if($facet == 'class_local_iln') {
            $facets = $this->formatTBFacet($facets, $results);
        }

        return $this->formatResponse(compact('facets'));
    }

    /**
     * Formats the given facet list. Changes the value of parent facets to search for all child facets.
     * Updates the fields "isApplied", "value", "href"
     *
     * @param array $facetList Facet list to format.
     * @param Results $result Result object from the search.
     *
     * @return array
     */
    public function formatTBFacet($facetList, $result)
    {
        $escape = true;
        $urlHelper = $result->getUrlQuery();

        foreach ($facetList as $key => $parentFacet) {
            if (!isset($parentFacet['children']) || empty($parentFacet['children'])) {
                continue;
            }

            $parentFacet['value'] = $parentFacet['tb_facet_value'];
            $isApplied = $result->getParams()->hasFilter($parentFacet['value']);

            if ($isApplied) {
                $href = $urlHelper->removeFilter($parentFacet['value'])->getParams($escape);
            } else {
                $href = $urlHelper->addFilter($parentFacet['value'])->getParams($escape);
            }
            $facetList[$key]['href'] = $href;
            $facetList[$key]['isApplied'] = $isApplied;
        }

        return $facetList;
    }
}