<?php

namespace ThULB\Controller;
use VuFind\Controller\CartController as OriginalCartController;

/**
 * Book Bag / Bulk Action Controller
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class CartController extends OriginalCartController
{
    public function homeAction()
    {
        $this->layout()->setVariable('showBreadcrumbs', false);        
        return parent::homeAction();
    }
    
    public function processorAction()
    {
        $this->layout()->setVariable('showBreadcrumbs', false);        
        return parent::processorAction();
    }
}
