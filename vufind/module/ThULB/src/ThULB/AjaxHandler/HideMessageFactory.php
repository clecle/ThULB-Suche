<?php

namespace ThULB\AjaxHandler;

use Interop\Container\ContainerInterface;

class HideMessageFactory implements \Zend\ServiceManager\Factory\FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container Service manager
     * @param string $requestedName Service being created
     * @param null|array $options Extra options (optional)
     *
     * @return object
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        return new $requestedName(
            $container->get('VuFind\SessionManager')
        );
    }
}