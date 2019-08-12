<?php

namespace ThULB\RecordTab;

use VuFind\RecordTab\AbstractBase;

class OnlineAccess extends AbstractBase
{
    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription() {
        return 'Access';
    }

    public function getRecordDriver() {
        return parent::getRecordDriver();
    }
}