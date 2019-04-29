<?php
/**
 * Permission Provider Factory Class
 *
 * PHP version 5
 *
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
 * @package  Authorization
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
namespace ThULB\Role\PermissionProvider;
use Zend\ServiceManager\ServiceManager;

/**
 * Permission Provider Factory Class
 *
 * @category ThULB
 * @package  Authorization
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for QueriedCookie
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return QueriedCookie
     */
    public static function getQueriedCookie(ServiceManager $sm)
    {
        return new QueriedCookie($sm->get('Request'), $sm->get('VuFind\CookieManager'));
    }
}
