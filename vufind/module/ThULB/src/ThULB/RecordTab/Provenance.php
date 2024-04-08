<?php

namespace ThULB\RecordTab;

use VuFind\RecordTab\AbstractBase as AbstractTab;

class Provenance extends AbstractTab
{
    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription() : string {
        return 'Provenances';
    }

    public function isVisible()
    {
        return !empty($this->driver->getProvenance());
    }
}