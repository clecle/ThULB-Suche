<?php
/**
 * AJAX handler to look up DOI data.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace ThULB\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;
use VuFindSearch\Backend\Solr\Backend;

/**
 * AJAX handler to look up fulltext data.
 */
class FulltextLookup extends AbstractBase
{
    /**
     * Solr search backend
     *
     * @var Backend
     */
    protected $solrBackend;

    /**
     * Constructor
     *
     * @param Backend $solrBackend Solr search backend
     */
    public function __construct(Backend $solrBackend) {
        $this->solrBackend = $solrBackend;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $response = [];
        $fulltextPPNs = (array)$params->fromQuery('fulltext', []);

        $results = $this->solrBackend->retrieveBatch($fulltextPPNs);
        /* @var $result \ThULB\RecordDriver\SolrVZGRecord */
        foreach ($results as $result) {
            if($result->isFormat('eBook|eJournal', true)) {
                $holdings = $result->getHoldings();
                if($remote = $holdings['holdings']['Remote'] ?? false) {
                    foreach($remote['items'] as $item) {
                        if($item['status'] == 'available') {
                            $response[$result->getUniqueID()] = array (
                                'desc' => $result->translate('Full text online'),
                                'link' => $item['remotehref'],
                            );
                            break;
                        }
                    }
                }
            }
            elseif ($result->isFormat('electronic Article')) {
                if($fulltextURL = $result->getFullTextURL()) {
                    $response[$result->getUniqueID()] = $fulltextURL;
                }
            }
        }
        return $this->formatResponse($response);
    }
}
