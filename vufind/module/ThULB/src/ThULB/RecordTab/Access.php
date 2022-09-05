<?php

namespace ThULB\RecordTab;

use Exception;
use VuFind\RecordDriver\AbstractBase;
use VuFind\RecordTab\AbstractBase as AbstractTab;

class Access extends AbstractTab
{
    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription() : string {
        return 'Access';
    }

    /**
     * Get the record driver
     *
     * @return AbstractBase
     *
     * @throws Exception
     */
    public function getRecordDriver() : AbstractBase {
        // make function public
        return parent::getRecordDriver();
    }
}