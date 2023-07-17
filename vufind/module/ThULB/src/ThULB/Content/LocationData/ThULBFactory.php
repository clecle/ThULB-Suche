<?php

namespace ThULB\Content\LocationData;

use Exception;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Throwable;

class ThULBFactory implements FactoryInterface
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
     * @throws ServiceNotFoundException   if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *                                    creating a service.
     * @throws ContainerException
     * @throws Throwable
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) : object {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }

        $thulbConfig = $container->get(\VuFind\Config\PluginManager::class)->get('thulb');

        $object = new $requestedName($thulbConfig);

        if (!empty($thulbConfig->Location->cacheAdapter)) {
            $cacheConfig = $thulbConfig->Location->toArray();
            $options = $cacheConfig['cacheOptions'] ?? [];
            if (empty($options['namespace'])) {
                $options['namespace'] = 'TESTmyROOTY';
            }
            if (empty($options['ttl'])) {
                $options['ttl'] = 300;
            }
            $settings = [
                'name' => $cacheConfig['cacheAdapter'],
                'options' => $options,
            ];
            $cache = $container
                ->get(\Laminas\Cache\Service\StorageAdapterFactory::class)
                ->createFromArrayConfiguration($settings);
            $object->setCache($cache);
        }

        return $object;
    }
}
