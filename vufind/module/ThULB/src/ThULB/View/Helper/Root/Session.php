<?php

namespace ThULB\View\Helper\Root;

use Zend\Session\SessionManager;
use Zend\View\Helper\AbstractHelper;

class Session extends AbstractHelper
{
    private $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function isMessageDisplayed($identifier) {

        $value = 0;
        $identifier = $identifier . '_expires';

        if($this->sessionManager->sessionExists()) {
            $value = $this->sessionManager->getStorage()->offsetGet($identifier);
        }

        return $value < time();
//        return true;
    }
}