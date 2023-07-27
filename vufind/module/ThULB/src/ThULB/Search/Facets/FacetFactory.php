<?php

namespace ThULB\Search\Facets;

use Exception;
use Psr\Container\ContainerInterface;

class FacetFactory
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
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, string $requestedName,
                             array $options = null
    ) : object {
        if (!empty($options)) {
            throw new Exception('Unexpected options passed to factory.');
        }
        return new $requestedName(
            $container->get(\VuFind\Config\PluginManager::class)
        );
    }
}