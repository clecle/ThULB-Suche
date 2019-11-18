<?php

namespace ThBIB\View\Helper\Root;

use VuFind\Search\UrlQueryHelper;
use Zend\View\Helper\AbstractHelper;

class RemoveThBibFilter extends AbstractHelper
{
    /**
     * Remove all Filters for the field "class_local_iln".
     *
     * @param UrlQueryHelper $query String to format
     *
     * @return UrlQueryHelper
     */
    public function __invoke($query)
    {
        $params = $query->getParamArray();
        if(isset($params['filter'])) {
            foreach ($params['filter'] as $filter) {
                if (strpos($filter, 'class_local_iln') !== false) {
                    $query = $query->removeFilter($filter);
                }
            }
        }
        return $query;
    }
}
