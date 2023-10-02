<?php

namespace ThULB\AjaxHandler;

use Exception;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class GetResultCountFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container Service manager
     * @param string             $requestedName Service being created
     * @param array|null         $options Extra options (optional)
     *
     * @return object
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    ) : object {
        if (!empty($options)) {
            throw new Exception('Unexpected options passed to factory.');
        }
        return new $requestedName(
            $container->get('VuFind\SearchRunner'),
            $container->get('ViewRenderer')
        );
    }
}