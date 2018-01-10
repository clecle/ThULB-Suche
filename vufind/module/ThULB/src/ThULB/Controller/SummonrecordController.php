<?php

namespace ThULB\Controller;
use Zend\Mvc\MvcEvent;
use VuFind\Controller\SummonrecordController as OriginalSummonrecordController;

/**
 * Overrides the standard version in VuFind\Controller\SummonrecordController
 * and replaces it via mudule configuration
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class SummonrecordController extends OriginalSummonrecordController
{
    /**
     * Use preDispatch event to add Summon message.
     *
     * @param MvcEvent $e Event object
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function injectSummonMessage(MvcEvent $e)
    {
        $this->layout()->poweredBy = '';
    }
}
