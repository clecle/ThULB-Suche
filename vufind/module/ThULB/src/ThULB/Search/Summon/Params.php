<?php

namespace ThULB\Search\Summon;

use VuFind\Search\Summon\Params as OriginalParams;
use VuFindSearch\ParamBag;

class Params extends OriginalParams
{
    /**
     * Set up filters based on VuFind settings.
     *
     * @param ParamBag $params Parameter collection to update
     *
     * @return void
     */
    public function createBackendFilterParameters(ParamBag $params) {
        parent::createBackendFilterParameters($params);

        $array = $params->getArrayCopy();
        $filterIndex = isset($array['filters']) ?
            array_search('includeNewspapers,true', $array['filters']) :
            false;
        if($filterIndex !== false) {
            unset($array['filters'][$filterIndex]);
        }
        else {
            $array['filters'][] = "ContentType,Newspaper Article,true";
        }
        $params->exchangeArray($array);
    }
}