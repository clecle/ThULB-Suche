<?php

namespace ThULB\Mailer;

use Interop\Container\ContainerInterface;
use VuFind\Mailer\Factory as OriginalFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Factory extends OriginalFactory
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    ) {
        $class = parent::__invoke($container, $requestedName, $options);

        $config = $container->get('VuFind\Config\PluginManager')->get('config');
        if(isset($config->Mail->default_reply_to) && !empty($config->Mail->default_reply_to)) {
            $class->setDefaultReplyTo($config->Mail->default_reply_to);
        }

        return $class;
    }
}