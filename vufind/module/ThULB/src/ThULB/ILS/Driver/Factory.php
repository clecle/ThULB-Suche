<?php

namespace ThULB\ILS\Driver;
use Zend\ServiceManager\ServiceManager;

/**
 * A class to provide factory methods for custom ils drivers.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory {
    public static function getPAIA(ServiceManager $sm)
    {
        return new PAIA(
            $sm->getServiceLocator()->get('VuFind\DateConverter'),
            $sm->getServiceLocator()->get('VuFind\SessionManager')
        );
    }
}
