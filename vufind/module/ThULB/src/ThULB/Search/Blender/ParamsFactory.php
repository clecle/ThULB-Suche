<?php
/**
 * Factory for Solr search params objects.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace ThULB\Search\Blender;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Throwable;
use VuFind\Search\Params\ParamsFactory as OriginalParamsFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class ParamsFactory extends OriginalParamsFactory
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
     * @throws Exception
     * @throws ContainerException
     * @throws Throwable
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    ) : object {
        if (!empty($options)) {
            throw new Exception('Unexpected options sent to factory.');
        }

        $configLoader = $container->get(\VuFind\Config\PluginManager::class);
        $blenderConfig = $configLoader->get('Blender');
        $backendConfig = $blenderConfig->Backends
            ? $blenderConfig->Backends->toArray() : [];
        if (!$backendConfig) {
            throw new \Exception('No backends enabled in Blender.ini');
        }

        $facetHelper
            = $container->get(\VuFind\Search\Solr\HierarchicalFacetHelper::class);
        $facetManager = $container
            ->get(\ThULB\Search\Facets\PluginManager::class);

        $searchParams = [];
        $paramsManager = $container->get(\VuFind\Search\Params\PluginManager::class);
        foreach (array_keys($backendConfig) as $backendId) {
            $searchParams[] = $paramsManager->get($backendId);
        }

        $yamlReader = $container->get(\VuFind\Config\YamlReader::class);
        $blenderMappings = $yamlReader->get('BlenderMappings.yaml');
        return parent::__invoke(
            $container,
            $requestedName,
            [$facetHelper, $searchParams, $blenderConfig, $blenderMappings, $facetManager]
        );
    }
}
