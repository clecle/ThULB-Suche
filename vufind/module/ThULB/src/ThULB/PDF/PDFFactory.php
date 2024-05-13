<?php

namespace ThULB\PDF;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Throwable;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class PDFFactory
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
     * @throws ServiceNotFoundException         if unable to resolve the service.
     * @throws ServiceNotCreatedException       if an exception is raised when creating a service.
     * @throws ContainerException
     * @throws Throwable
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    ) : object {
        return new $requestedName(
            $container->get(\Laminas\View\Renderer\PhpRenderer::class)->plugin('translate')
        );
    }
}
