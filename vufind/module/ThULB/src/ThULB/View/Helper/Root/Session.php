<?php

namespace ThULB\View\Helper\Root;

use Zend\Session\SessionManager;
use Zend\View\Helper\AbstractHelper;

class Session extends AbstractHelper
{
    private $sessionManager;

    public function __construct(SessionManager $sessionManager) {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Checks if a message with the given identifier should be displayed.
     *
     * @param $identifier
     *
     * @return bool
     */
    public function isMessageDisplayed($identifier) {

        $value = 0;
        $identifier = $identifier . '_expires';

        if($this->sessionManager->sessionExists()) {
            $value = $this->sessionManager->getStorage()->offsetGet($identifier);
        }

        return $value < time();
    }
}