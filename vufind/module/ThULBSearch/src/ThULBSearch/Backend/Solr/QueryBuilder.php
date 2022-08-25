<?php

namespace ThULBSearch\Backend\Solr;

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
    protected function getNormalizedQueryString($query) : string
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
    protected function ignoreOperators(string $queryString, array $operatorsToIgnore) : string{
        $pattern = '/([' . preg_quote(implode('', $operatorsToIgnore), '/') . '])/';
        return preg_replace($pattern, "\\\\$1", $queryString);
    }
}