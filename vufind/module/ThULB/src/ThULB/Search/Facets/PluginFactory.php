<?php

namespace ThULB\Search\Facets;

use Psr\Container\ContainerInterface;
use VuFind\ServiceManager\AbstractPluginFactory;

class PluginFactory extends AbstractPluginFactory
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->defaultNamespace = 'ThULB\Search';
        $this->classSuffix = '\Facets';
    }

    /**
     * Create a service for the specified name.
     *
     * @param ContainerInterface $container     Service container
     * @param string             $requestedName Name of service
     * @param array|null         $extras        Extra options
     *
     * @return object
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $extras = null
    ) : object {
        $class = $this->getClassName($requestedName);
        // Clone the options instance in case caller modifies it:
        return new $class();
    }
}
