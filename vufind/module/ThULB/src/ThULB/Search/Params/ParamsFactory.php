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
 * @package  Search_Params
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace ThULB\Search\Params;

use Exception;
use Interop\Container\ContainerInterface;
use VuFind\Http\PhpEnvironment\Request;
use VuFind\Search\Params\ParamsFactory as OriginalParamsFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class ParamsFactory extends OriginalParamsFactory
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
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    ) {
        $params = parent::__invoke($container, $requestedName, $options);

        $lookfor = $container->get(Request::class)->getQuery('lookfor')
            ?? $container->get(Request::class)->getPost('lookfor');

        if(empty(trim($lookfor)) && is_callable([$params->getOptions(), 'setResultLimit'])) {
            $params->getOptions()->setResultLimit(400);
        }
        return $params;
    }
}
