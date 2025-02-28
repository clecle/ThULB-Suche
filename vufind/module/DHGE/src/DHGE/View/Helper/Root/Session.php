<?php

namespace DHGE\View\Helper\Root;

use Laminas\Session\Container as SessionContainer;
use Laminas\View\Helper\AbstractHelper;

class Session extends AbstractHelper
{
    protected SessionContainer $paiaSession;

    public function __construct(SessionContainer $paiaSession) {
        $this->paiaSession = $paiaSession;
    }

    /**
     * Returns the library of the logged-in user from the session.
     *
     * @return string|null
     */
    public function getLibraryPaia() : ?string {
        return $this->paiaSession->libraryPaia ?? null;
    }
}
