<?php
/**
 * Description
 *
 * PHP version 5
 *
 * Copyright (C) Verbundzentrale des GBV, Till Kinstler 2014.
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
 * @package  RecordDrivers
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace EAH\RecordDriver;

use \ThULB\RecordDriver\SolrVZGRecord as OriginalSolrVZGRecord;

class SolrVZGRecord extends OriginalSolrVZGRecord
{
    const LIBRARY_ILN = '150';
}