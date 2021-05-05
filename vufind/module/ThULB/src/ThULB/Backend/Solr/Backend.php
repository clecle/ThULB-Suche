<?php
/**
 * SOLR backend.
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
namespace ThULB\Backend\Solr;

use VuFindSearch\Backend\Solr\Backend as OriginalBackend;
use VuFindSearch\ParamBag;
use VuFindSearch\Response\RecordCollectionInterface;

/**
 * SOLR backend.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class Backend extends OriginalBackend
{
    /**
     * Retrieve a batch of documents.
     *
     * @param array    $ids    Array of document identifiers
     * @param ParamBag $params Search backend parameters
     *
     * @return RecordCollectionInterface
     */
    public function retrieveBatch($ids, ParamBag $params = null)
    {
        $params = $params ?: new ParamBag();

        // Load 100 records at a time; this is a good number to avoid memory
        // problems while still covering a lot of ground.
        // ThULB: changed to 40 because solr server from GBV rejects requests with 50 or more 'OR's
        $pageSize = 40;

        // Callback function for formatting IDs:
        $formatIds = function ($i) {
            return '"' . addcslashes($i, '"') . '"';
        };

        // Retrieve records a page at a time:
        $results = false;
        while (count($ids) > 0) {
            $currentPage = array_splice($ids, 0, $pageSize, []);
            $currentPage = array_map($formatIds, $currentPage);
            $params->set('q', 'id:(' . implode(' OR ', $currentPage) . ')');
            $params->set('start', 0);
            $params->set('rows', $pageSize);
            $this->injectResponseWriter($params);
            $next = $this->createRecordCollection(
                $this->connector->search($params)
            );
            if (!$results) {
                $results = $next;
            } else {
                foreach ($next->getRecords() as $record) {
                    $results->add($record);
                }
            }
        }
        $this->injectSourceIdentifier($results);
        return $results;
    }
}
