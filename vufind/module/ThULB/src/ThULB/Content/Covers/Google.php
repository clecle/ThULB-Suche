<?php

namespace ThULB\Content\Covers;

use VuFind\Content\Covers\Google as OriginalGoogle;

class Google extends OriginalGoogle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->supportsIsbn = $this->cacheAllowed = true;
    }
}
