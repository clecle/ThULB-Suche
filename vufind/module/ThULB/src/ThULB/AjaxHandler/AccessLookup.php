<?php

namespace ThULB\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\PhpRenderer;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\RecordDriver\DefaultRecord;
use VuFind\Search\BackendManager;

class AccessLookup extends AbstractBase
{
    protected BackendManager $backendManager;
    protected PhpRenderer $phpRenderer;

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
    public function handleRequest(Params $params) : array {
        $response = [];
        $accessLookup = (array) $params->fromPost('accessLookup', []);

        $requestedIDs = array ();
        foreach ($accessLookup as $lookUp) {
            list($source, $id) = explode(':', $lookUp);
            $requestedIDs[$source][] = $id;
        }

        foreach($requestedIDs as $source => $ids) {
            $results = $this->backendManager->get($source)->retrieveBatch($ids);
            foreach ($results as $result) {
                $response[] = array (
                    'id' => $source . ':' . $result->getUniqueID(),
                    'links' => array_merge(
                        $this->getHoldAndReserveLink($result),
                        $this->getOnlineContentLinks($result)
                    )
                );
            }
        }

        return $this->formatResponse($response);
    }

    protected function getOnlineContentLinks(DefaultRecord $driver) : array {
        $onlineContentLinks = $this->phpRenderer->thulb_onlineContent($driver);

        $addedTypes = array ();
        $html = array ();
        foreach ($onlineContentLinks as $linkData) {
            // only show one link of each type in the result view
            if(in_array($linkData['type'], $addedTypes)) {
                continue;
            }
            $addedTypes[] = $linkData['type'];

            $html[] = trim(
                $this->phpRenderer->record($driver)
                    ->renderTemplate('onlineContent.phtml', ['linkData' => $linkData, 'additionalBtnClass' => 'btn-xs'])
            );
        }

        if($html) {
            $html[] = $this->phpRenderer->render('record/broken-link.phtml', ['driver' => $driver]);
        }

        return $html;
    }

    protected function getHoldAndReserveLink(DefaultRecord $driver) : array {
        if($driver->getSourceIdentifier() != 'Solr') {
            return [];
        }

        $holdings = $driver->tryMethod('getHoldingsToOrderOrReserve');
        if(empty($holdings)) {
            return [];
        }

        // if there is only one item, use order or reserve URL directly
        if(count($holdings) == 1) {
            $location = $holdings[array_key_first($holdings)];
            if(count($location) == 1) {
                $holdingHelper = $this->phpRenderer->thulb_holdingHelper();
                if($location[0]['availability']){
                    $link = $holdingHelper->getRequestLinks($location[0], $driver->isNewsPaper())[0];
                }
                else {
                    $link = $holdingHelper->getRecallLink($location[0]);
                }

                return $link ? [$this->createOrderReserveButton($link)] : [];
            }
        }

        // more than one item
        $itemCanBeOrdered = $itemCanBeReserved = false;
        foreach ($holdings ?? [] as $location) {
            foreach ($location as $item) {
                $itemCanBeOrdered = $itemCanBeOrdered || $item['availability'];
                $itemCanBeReserved = $itemCanBeReserved || !$item['availability'];
            }
        }

        if($itemCanBeOrdered && $itemCanBeReserved) {
            $msg = 'Place a Hold / Recall This';
        }
        else {
            $msg = $itemCanBeOrdered ? 'Place a Hold' : 'Recall This';
        }

        return [$this->createOrderReserveButton([
            'classes' => 'btn btn-primary btn-xs',
            'link' => $this->phpRenderer->url('record-orderreserve', ['id' => $driver->getUniqueID()]),
            'desc' => $msg
        ])];
    }

    protected function createOrderReserveButton(array $linkData) : string {
        if(empty($linkData)) {
            return '';
        }
        return sprintf(
            "<a class=\"%s\" href=\"%s\" data-lightbox>%s</a>",
            $linkData['classes'],
            $linkData['link'],
            $this->phpRenderer->transEsc($linkData['desc'])
        );
    }
}
