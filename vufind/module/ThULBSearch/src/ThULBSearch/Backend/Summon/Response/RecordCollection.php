<?php

namespace ThULBSearch\Backend\Summon\Response;

use VuFindSearch\Backend\Summon\Response\RecordCollection as OriginalRecordCollection;

class RecordCollection extends OriginalRecordCollection
{
    protected array $blenderFacets = [];

    /**
     * Return facet information.
     *
     * @return array
     */
    public function getFacets()
    {
        if(!$this->blenderFacets) {
            $blenderFacets = [];

            foreach ($this->response['facetFields'] as $facetField) {
                foreach ($facetField['counts'] as $facetItem) {
                    $blenderFacets[$facetField['displayName']][$facetItem['value']] = $facetItem['count'];
                }
            }

            $this->blenderFacets = $blenderFacets;
        }

        return $this->blenderFacets;
    }
}
