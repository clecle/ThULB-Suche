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
use Laminas\View\Renderer\PhpRenderer;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\Search\BackendManager;
use VuFindSearch\Backend\Solr\Backend;

/**
 * AJAX handler to look up fulltext data.
 */
class OnlineContentLookup extends AbstractBase
{
    /**
     * Solr search backend
     *
     * @var BackendManager
     */
    protected $backendManager;
    /**
     * @var PhpRenderer
     */
    private $phpRenderer;

    /**
     * Constructor
     *
     * @param BackendManager $backendManager
     * @param PhpRenderer $phpRenderer
     */
    public function __construct(BackendManager $backendManager, PhpRenderer $phpRenderer) {
        $this->backendManager = $backendManager;
        $this->phpRenderer = $phpRenderer;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params) : array
    {
        $response = [];
        $onlineContent = (array) $params->fromPost('onlineContent', []);

        $onlineContentIDs = array ();
        foreach ($onlineContent as $lookUp) {
            list($source, $id) = preg_split('/:/', $lookUp);
            $onlineContentIDs[$source][] = $id;
        }

        foreach($onlineContentIDs as $source => $ids) {
            $results = $this->backendManager->get($source)->retrieveBatch($ids);
            foreach ($results as $result) {
                $onlineContentLinks = $this->phpRenderer->thulb_onlineContent($result);

                $addedTypes = array ();
                $html = array ();
                foreach ($onlineContentLinks as $linkData) {
                    // only show one link of each type in the result view
                    if(in_array($linkData['type'], $addedTypes)) {
                        continue;
                    }
                    $addedTypes[] = $linkData['type'];

                    $html[] = trim(
                        $this->phpRenderer->record($result)
                            ->renderTemplate('onlineContent.phtml', ['linkData' => $linkData, 'additionalBtnClass' => 'btn-xs'])
                    );
                }

                if($html) {
                    $html[] = $this->phpRenderer->render('record/broken-link.phtml', ['driver' => $result]);
                }

                $response[] = array (
                    'id' => $source . ':' . $result->getUniqueID(),
                    'links' => $html
                );
            }
        }

        return $this->formatResponse($response);
    }
}
