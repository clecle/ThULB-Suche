<?php

namespace ThULB\Controller;
use Zend\Mvc\MvcEvent;
use VuFind\Controller\SummonController as OriginalSummonController;

/**
 * Overrides the standard version in VuFind\Controller\SummonController and
 * replaces it via mudule configuration
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class SummonController extends OriginalSummonController
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
