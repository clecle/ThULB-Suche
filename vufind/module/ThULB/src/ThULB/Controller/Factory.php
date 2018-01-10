<?php

namespace ThULB\Controller;
use  Zend\ServiceManager\ServiceManager;

/**
 * Factory to load our controllers.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory
{
    /**
     * Construct the AjaxController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return AjaxController
     */
    public function getAjaxController(ServiceManager $sm)
    {
        return new AjaxController($sm->getServiceLocator());
    }

    /**
     * Construct the CartController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return CartController
     */
    public static function getCartController(ServiceManager $sm)
    {
        return new CartController(
            $sm->getServiceLocator(),
            new \Zend\Session\Container(
                'cart_followup',
                $sm->getServiceLocator()->get('VuFind\SessionManager')
            )
        );
    }
    
    /**
     * Construct the MyResearchController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return MyResearchController
     */
    public function getMyResearchController(ServiceManager $sm)
    {
        return new MyResearchController($sm->getServiceLocator());
    }
    
    /**
     * Construct the SummonController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SummonController
     */
    public function getSummonController(ServiceManager $sm)
    {
        return new SummonController($sm->getServiceLocator());
    }
    
    /**
     * Construct the SummonrecordController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SummonrecordController
     */
    public function getSummonrecordController(ServiceManager $sm)
    {
        return new SummonrecordController($sm->getServiceLocator());
    }
}
