<?php
/**
 * Factory methods for custom database table row classes
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2015.
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
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
 * @category ThULB
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\Db\Row;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceManager;
use VuFind\Db\Row\UserFactory as OriginalFactory;

/**
 * Description of Factory
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory extends OriginalFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory!');
        }
        $config = $container->get('VuFind\Config\PluginManager')->get('config');
        $privacy = isset($config->Authentication->privacy)
            && $config->Authentication->privacy;
        $rowClass = $privacy ? $this->privateUserClass : $requestedName;
        $prototype = parent::__invoke($container, $rowClass, $options);
        $prototype->setConfig($config);
        if ($privacy) {
            $sessionManager = $container->get('Laminas\Session\SessionManager');
            $session = new \Laminas\Session\Container('Account', $sessionManager);
            $prototype->setSession($session);
        }
        return $prototype;
    }
}
