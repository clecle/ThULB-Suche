<?php
/**
 * Metadata view helper test class
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

namespace DHGETest\View\Helper\Root;

/**
 * Test class for the record data formatters zdb number view helper functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class ZdbNumberMetadataTest extends AbstractRecordDataFormatterTest
{
    protected ?string $sheetName = 'ZDB-Nummer';
    protected ?string $metadataKey = 'ZDB';
    protected ?string $template = 'data-zdb.phtml';
}
