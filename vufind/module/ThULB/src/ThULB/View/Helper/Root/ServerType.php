<?php
/**
 * Helper class to provide the VUFIND_ENV superglobal content
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace ThULB\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;

/**
 * Helper class to provide the VUFIND_ENV superglobal content
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class ServerType extends AbstractHelper
{
    /**
     * Get the VuFind environment variable value.
     *
     * @return String
     */
    public function __invoke()
    {
        return $_SERVER['VUFIND_ENV'];
    }
}
