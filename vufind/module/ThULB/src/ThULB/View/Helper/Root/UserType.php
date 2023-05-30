<?php

namespace ThULB\View\Helper\Root;

use Laminas\Session\Container as SessionContainer;
use Laminas\Session\SessionManager;
use Laminas\View\Helper\AbstractHelper;

class UserType extends AbstractHelper
{
    protected ?SessionContainer $session = null;
    protected SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager) {
        $this->sessionManager = $sessionManager;
    }


    /**
     * Returns the user type of the logged-in user from the session
     *
     * @return int
     */
    public function __invoke() : int {
        return $this->getSession()->userType ?? -1;
    }

    /**
     * Get the session container (constructing it on demand if not already present)
     *
     * @param string $name Name of the session namespace
     *
     * @return SessionContainer
     */
    protected function getSession(string $name = 'PAIA') : SessionContainer {
        // SessionContainer not defined yet? Build it now:
        if ($this->session === null) {
            $this->session = new SessionContainer($name, $this->sessionManager);
        }

        return $this->session;
    }
}