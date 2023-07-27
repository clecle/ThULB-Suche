<?php
namespace ThULB\View\Helper\Root;

use Exception;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Throwable;

class ServiceDeskFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param array|null         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException   if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *                                    creating a service.
     * @throws ContainerException
     * @throws Throwable
     * @throws Exception
     */
    public function __invoke (ContainerInterface $container, $requestedName, array $options = null) : object {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }

        return new $requestedName(
            $container->get(\VuFind\Config\PluginManager::class)->get('thulb')
        );
    }
}
