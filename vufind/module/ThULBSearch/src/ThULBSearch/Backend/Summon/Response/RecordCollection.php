<?php

/**
 * WorldCat record collection.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
namespace ThULBSearch\Backend\Summon\Response;

use VuFindSearch\Backend\Summon\Response\RecordCollection as OriginalRecordCollection;

/**
 * WorldCat record collection.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
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
