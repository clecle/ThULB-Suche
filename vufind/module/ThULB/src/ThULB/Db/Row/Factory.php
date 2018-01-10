<?php

namespace ThULB\Db\Row;
use Zend\ServiceManager\ServiceManager,
    VuFind\Db\Row\Factory as OriginalFactory;

/**
 * Description of Factory
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory extends OriginalFactory
{
    /**
     * Construct the User row prototype.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return OAuthUser
     */
    public static function getUser(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        // Use a special row class when we're in privacy or oauth mode:
        $privacy = isset($config->Authentication->privacy)
            && $config->Authentication->privacy;
        $oauth = isset($config->Authentication->oauth)
            && $config->Authentication->oauth;
        $rowClass = $oauth ? 'ThULB\Db\Row\OAuthUser' : ('VuFind\Db\Row\\' . ($privacy ? 'PrivateUser' : 'User'));
        $prototype = static::getGenericRow($rowClass, $sm);
        $prototype->setConfig($config);
        if ($privacy) {
            $sessionManager = $sm->getServiceLocator()->get('VuFind\SessionManager');
            $session = new \Zend\Session\Container('Account', $sessionManager);
            $prototype->setSession($session);
        }
        return $prototype;
    }
}
