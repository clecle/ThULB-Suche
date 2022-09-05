<?php

namespace DHGE\Controller;

use Laminas\View\Model\ViewModel;
use ThULB\Controller\RecordController as OriginalRecordController;
use VuFind\Exception\PasswordSecurity;

class RecordController extends OriginalRecordController
{
    /**
     * Action for dealing with holds.
     *
     * @return mixed
     *
     * @throws PasswordSecurity
     */
    public function holdAction() {
        // Check if the requested item is available in the current library
        $availableInUserLibrary = false;

        $docId = $this->params()->fromQuery('doc_id', false);
        $itemId = $this->params()->fromQuery('item_id', false);
        if($this->getAuthManager()->isLoggedIn() && $docId && $itemId) {
            $ppn = substr($docId, strpos($docId, 'ppn:') + 4);
            $result = $this->getILS()->getHolding($ppn);

            $userLibrary = $this->getAuthManager()->getUserLibrary();
            foreach($result['holdings'] as $item) {
                if($item['library'] == $userLibrary && $item['item_id'] == $itemId) {
                    $availableInUserLibrary = true;
                    break;
                }
            }
        }

        if(!$availableInUserLibrary) {
            $this->flashMessenger()->addMessage('recall_not_available_in_your_library', 'warning');
        }

        $view = parent::holdAction();

        if($view instanceof ViewModel) {
            $view->setVariable('availableInUserLibrary', $availableInUserLibrary);
        }

        return $view;
    }
}