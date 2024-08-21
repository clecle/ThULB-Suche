<?php

namespace ThULB\Controller;

use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use VuFind\Controller\RecordController as OriginalRecordController;
use VuFind\Exception\PasswordSecurity;

class RecordController extends OriginalRecordController
{
    use ChangePasswordTrait;

    public function onDispatch(MvcEvent $event)
    {
        // ignore onDispatch from Trait to force only when certain actions are called
        return parent::onDispatch($event);
    }

    /**
     * Action for dealing with storage retrieval requests.
     *
     * @return mixed
     *
     * @throws PasswordSecurity
     */
    public function storageRetrievalRequestAction() {
        if($this->isPasswordChangeNeeded()) {
            return $this->forceNewPassword();
        }

        return parent::storageRetrievalRequestAction();
    }

    /**
     * Action for dealing with holds.
     *
     * @return mixed
     *
     * @throws PasswordSecurity
     */
    public function holdAction() {
        if($this->isPasswordChangeNeeded()) {
            return $this->forceNewPassword();
        }

        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        $view = parent::holdAction();

        if($view instanceof ViewModel) {
            $view->setVariable('duedate', $this->getRequest()->getQuery('duedate', null));
            $view->setVariable('requests_placed', $this->getRequest()->getQuery('requests_placed', null));

            $gatheredDetails = $view->getVariable('gatheredDetails');
            $holdings = $this->getILS()->getHolding($gatheredDetails['id'], $patron);
            foreach($holdings['holdings'] ?? [] as $item) {
                if($item['doc_id'] == $gatheredDetails['doc_id'] && $item['item_id'] == $gatheredDetails['item_id']) {
                    $view->setVariable('item', $item);
                    break;
                }
            }
        }

        return $view;
    }

    public function orderReserveAction() {
        // Force login if necessary:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }

        return new ViewModel([
            'driver' => $this->loadRecord()
        ]);
    }
}