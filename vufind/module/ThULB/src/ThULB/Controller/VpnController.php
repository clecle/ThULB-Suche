<?php

namespace ThULB\Controller;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use VuFind\Role\PermissionManager;

class VpnController extends \VuFind\Controller\AbstractBase {

    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
    }

    public function checkAction() {
        $serviceDeskUrl = $this->getRequest()->getQuery('sdurl');

        $inVPN = $this->serviceLocator->get(PermissionManager::class)->isAuthorized('access.VPN');
        if($inVPN) {
            return $this->redirect()->toUrl($serviceDeskUrl);
        }

        return new ViewModel([
            'serviceDeskUrl' => $serviceDeskUrl
        ]);
    }
}