<?php

namespace ThULB\Controller;
use VuFind\Controller\MyResearchController as OriginalController;


/**
 * Description of MyResearchController
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class MyResearchController extends OriginalController
{
    const ID_URI_PREFIX = 'http://uri.gbv.de/document/opac-de-27:ppn:';

    /**
     * User login action -- clear any previous follow-up information prior to
     * triggering a login process. This is used for explicit login links within
     * the UI to differentiate them from contextual login links that are triggered
     * by attempting to access protected actions.
     *
     * @return mixed
     */
    public function userloginAction()
    {
        $return = parent::userloginAction();
        $this->clearFollowupUrl();
        
        return $return;
    }
    
    /**
     * Send list of checked out books to view
     *
     * @return mixed
     */
    public function checkedoutAction()
    {
        $viewModel = parent::checkedoutAction();
        $viewModel->setVariable('renewForm', true);
        
        return $viewModel;
    }

    /**
     * We don't use this action anymore; it is replaced by the loans action, that
     * combines all items held by the patron and all provided items
     *
     * @return mixed
     */
    public function holdsAction()
    {
        return $this->redirect()->toRoute('default', ['controller' => 'myresearch', 'action' => 'holdsAndSRR']);
    }
    
    /**
     * We don't use this action anymore; it is replaced by the loans action, that
     * combines all items held by the patron and all provided items
     *
     * @return mixed
     */
    public function storageRetrievalRequestsAction()
    {
        return $this->redirect()->toRoute('default', ['controller' => 'myresearch', 'action' => 'holdsAndSRR']);
    }

    /**
     * Send list of books that are provided for the user to view
     *
     * @return mixed
     */
    public function providedAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Display account blocks, if any:
        $this->addAccountBlocksToFlashMessenger($catalog, $patron);

        // Get the current renewal status and process renewal form, if necessary:
        $renewStatus = $catalog->checkFunction('Renewals', compact('patron'));
        $renewResult = $renewStatus
            ? $this->renewals()->processRenewals(
                $this->getRequest()->getPost(), $catalog, $patron
            )
            : [];

        // We always want to display a renewal form:
        $renewForm = false;

        // Get checked out item details:
        $result = $catalog->getMyProvidedItems($patron);

        // Get page size:
        $config = $this->getConfig();
        $limit = isset($config->Catalog->checked_out_page_size)
            ? $config->Catalog->checked_out_page_size : 50;

        // Build paginator if needed:
        if ($limit > 0 && $limit < count($result)) {
            $adapter = new \Zend\Paginator\Adapter\ArrayAdapter($result);
            $paginator = new \Zend\Paginator\Paginator($adapter);
            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($this->params()->fromQuery('page', 1));
            $pageStart = $paginator->getAbsoluteItemNumber(1) - 1;
            $pageEnd = $paginator->getAbsoluteItemNumber($limit) - 1;
        } else {
            $paginator = false;
            $pageStart = 0;
            $pageEnd = count($result);
        }

        $transactions = $hiddenTransactions = [];
        foreach ($result as $i => $current) {
            // Add renewal details if appropriate:
            $current = $this->renewals()->addRenewDetails(
                $catalog, $current, $renewStatus
            );

            // Build record driver (only for the current visible page):
            if ($i >= $pageStart && $i <= $pageEnd) {
                $transactions[] = $this->getDriverForILSRecord($current);
            } else {
                $hiddenTransactions[] = $current;
            }
        }

        return $this->createViewModel(
            compact(
                'transactions', 'renewForm', 'renewResult', 'paginator',
                'hiddenTransactions'
            )
        );
    }

    /**
     * Send list of holds to view
     *
     * @return mixed
     */
    public function holdsAndSRRAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Process cancel requests if necessary:
        $cancelStatus = $catalog->checkFunction('cancelHolds', compact('patron'));
        $view = $this->createViewModel();
        $view->cancelResults = $cancelStatus
            ? $this->holds()->cancelHolds($catalog, $patron) : [];
        // If we need to confirm
        if (!is_array($view->cancelResults)) {
            return $view->cancelResults;
        }

        // We always want to display a cancel form:
        $view->cancelForm = true;
        $view->disableCheckboxes = $patron['status'] == 2;

        // Get held item details:
        $result = $catalog->getMyHoldsAndSRR($patron);
        $recordList = [];
        $this->holds()->resetValidation();
        foreach ($result as $current) {
            // Add cancel details if appropriate:
            $current = $this->holds()->addCancelDetails(
                $catalog, $current, $cancelStatus
            );

            // Build record driver:
            $recordList[] = $this->getDriverForILSRecord($current);
        }

        // Get List of PickUp Libraries based on patron's home library
        try {
            $view->pickup = $catalog->getPickUpLocations($patron);
        } catch (\Exception $e) {
            // Do nothing; if we're unable to load information about pickup
            // locations, they are not supported and we should ignore them.
        }
        $view->recordList = $recordList;
        return $view;
    }

    /**
     * Provide a link to the password change site of the ILS.
     *
     * @return view
     */
    public function changePasswordLinkAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }
        
        if (!$this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        }
        
        $view = $this->createViewModel($this->params()->fromPost());
        
        $view->setTemplate('myresearch/ilsaccountlink');
        return $view;
    }

    /**
     * Catalog Login Action
     *
     * @return mixed
     */
    public function catalogloginAction()
    {
        return $this->forwardTo('MyResearch', 'Login');
    }
    
    /**
     * Get a record driver object corresponding to an array returned by an ILS
     * driver's getMyHolds / getMyTransactions method.
     *
     * @param array $current Record information
     *
     * @return \VuFind\RecordDriver\AbstractBase
     */
    protected function getDriverForILSRecord($current)
    {
        $current['id'] = str_replace(self::ID_URI_PREFIX, '', $current['id']);
        
        return parent::getDriverForILSRecord($current);
    }
}