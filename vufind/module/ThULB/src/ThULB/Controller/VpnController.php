<?php

namespace ThULB\Controller;

use Laminas\Config\Config;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;

class VpnController extends \VuFind\Controller\AbstractBase {
    protected ?Config $config;

    public function __construct(ServiceLocatorInterface $serviceLocator,) {
        parent::__construct($serviceLocator);

        $this->config = $serviceLocator->get(\VuFind\Config\PluginManager::class)->get('thulb')->BrokenLink ?? null;
    }

    public function checkAction() {
        $serviceDeskUrl = $this->getRequest()->getQuery('sdurl');

        $display = $defaultDisplay = $this->config->defaultDisplay ?? true;

        // Check for exceptions from default
        if ($permission = $this->config?->permission ?? null) {
            if ($this->serviceLocator->get(\VuFind\Role\PermissionManager::class)->isAuthorized($permission)) {
                $display = !$defaultDisplay;
            }
        }

        if ($display) {
            return new ViewModel([
                'serviceDeskUrl' => $serviceDeskUrl
            ]);
        }
        else {
            return $this->redirect()->toUrl($serviceDeskUrl);
        }
    }
}