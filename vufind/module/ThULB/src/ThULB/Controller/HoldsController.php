<?php

namespace ThULB\Controller;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use VuFind\Controller\HoldsController as OriginalHoldsController;
use VuFind\RecordDriver\AbstractBase;
use VuFind\Validator\Csrf;

class HoldsController extends OriginalHoldsController
{
    /**
     * We don't use this action anymore; it is replaced by the loans action, that
     * combines all items held by the patron and all provided items
     *
     * @return mixed
     */
    public function listAction()
    {
        return $this->redirect()->toRoute('default', ['controller' => 'holds', 'action' => 'holdsAndSRR']);
    }

    public function __construct(ServiceLocatorInterface $sm, Csrf $csrf, StorageInterface $cache)
    {
        parent::__construct($sm, $csrf, $cache);
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
        $this->holds()->resetValidation();

        $result = array_map(function($current) use ($catalog, $cancelStatus) {
            // Add cancel details if appropriate:
            return $this->holds()->addCancelDetails(
                $catalog, $current, $cancelStatus
            );
        }, $result);

        $recordList = $this->ilsRecords()->getDrivers($result);

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
}