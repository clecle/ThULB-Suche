<?php

namespace ThULB\Backend\Solr;

use VuFindSearch\Backend\Solr\QueryBuilder as OriginalQueryBuilder;
use VuFindSearch\Query\Query;

class QueryBuilder extends OriginalQueryBuilder
{
    /**
     * Given a Query object, return a fully normalized version of the query string.
     *
     * @param Query $query Query object
     *
     * @return string
     */
    protected function getNormalizedQueryString($query)
    {
        // Allowed operators: && || ^ " ~ * ?
        $operatorsToIgnore = array('+', '-', '!', '(', ')', '{', '}', '[', ']', ':', '/');

        $queryString = $query->getString();
        $queryString = $this->getLuceneHelper()->normalizeSearchString($queryString);
        $queryString = $this->ignoreOperators($queryString, $operatorsToIgnore);
        $queryString = $this->fixTrailingQuestionMarks($queryString);

        return $queryString;
    }

    /**
     * Escape specified operators so that solr ignores them.
     *
     * @param string $queryString
     * @param array  $operatorsToIgnore
     *
     * @return string
     */
    protected function ignoreOperators($queryString, $operatorsToIgnore) {
        $pattern = '/([' . preg_quote(implode('', $operatorsToIgnore), '/') . '])/';
        $queryString = preg_replace($pattern, "\\\\$1", $queryString);

        return $queryString;
    }
}