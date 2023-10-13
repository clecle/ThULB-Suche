<?php

namespace ThULB\View\Helper\Root;

use VuFind\View\Helper\Root\Session as OriginalSessionHelper;

class Session extends OriginalSessionHelper
{
    /**
     * Checks if a message with the given identifier should be displayed.
     *
     * @param $identifier
     *
     * @return bool
     */
    public function isMessageDisplayed($identifier) : bool {
        $value = $this->sessionContainer->{$identifier . '_expires'} ?? 0;

        return $value < time();
    }
}