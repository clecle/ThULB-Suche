<?php

namespace ThULB\Controller;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\Response;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Container as SessionContainer;
use Laminas\View\Model\ViewModel;
use VuFind\Controller\HoldsController as OriginalHoldsController;
use VuFind\Validator\SessionCsrf;

class HoldsController extends OriginalHoldsController
{
    /**
     * We don't use this action anymore; it is replaced by the loans action, that
     * combines all items held by the patron and all provided items
     *
     * @return Response
     */
    public function listAction() : Response
    {
        return $this->redirect()->toRoute('default', ['controller' => 'holds', 'action' => 'holdsAndSRR']);
    }

    public function __construct(ServiceLocatorInterface $sm, SessionCsrf $csrf, StorageInterface $cache)
    {
        parent::__construct($sm, $csrf, $cache);
    }

    /**
     * Send list of holds to view
     *
     * @return ViewModel
     */
    public function holdsAndSRRAction() : ViewModel
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
        // disable checkboxes if user has status '2' or does not have the permission to renew items
        $paiaSession = new SessionContainer('PAIA', $this->serviceLocator->get(\Laminas\Session\SessionManager::class));
        $view->disableCheckboxes = $patron['status'] == 2 || !in_array('write_items', $paiaSession->scope);

        // Get held item details:
        $result = $catalog->getMyHoldsAndSRR($patron);
        $this->holds()->resetValidation();

        $result = array_map(function($current) use ($catalog, $cancelStatus) {
            // Add cancel details if appropriate:
            return $this->holds()->addCancelDetails(
                $catalog, $current, $cancelStatus
            );
        }, $result);

        // Get List of PickUp Libraries based on patron's home library
        try {
            $view->pickup = $catalog->getPickUpLocations($patron);
        }
        catch (\Exception $e) {
            // Do nothing; if we're unable to load information about pickup
            // locations, they are not supported and we should ignore them.
        }

        $orderedList = array ();
        $reservedList = array ();
        foreach ($this->ilsRecords()->getDrivers($result) as $record) {
            if(($record->getExtraDetail('ils_details')['type'] ?? false) == 'reserved') {
                $reservedList[] = $record;
            }
            else {
                $orderedList[] = $record;
            }
        }

        $view->orderedList = $orderedList;
        $view->reservedList = $reservedList;

        return $view;
    }
}