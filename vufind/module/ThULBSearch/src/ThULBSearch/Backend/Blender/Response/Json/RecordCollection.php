<?php

/**
 * JSON-based record collection for records from multiple sources.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  Search
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
namespace ThULBSearch\Backend\Blender\Response\Json;

use VuFindSearch\Backend\Blender\Response\Json\RecordCollection as OriginalRecordCollection;

class RecordCollection extends OriginalRecordCollection
{
    /**
     * Map facet values from the backends into a merged list
     *
     * @param array $collections Result collections
     * @param array $settings    Settings for a single facet field
     *
     * @return array
     */
    protected function mapFacetValues(array $collections, array $settings): array
    {
        $result = parent::mapFacetValues($collections, $settings);

        foreach ($settings['Mappings'] as $backendId => $mappings) {
            if (($collections[$backendId] ?? false) && ($ignore = $mappings['Ignore'] ?? false)) {
                foreach (is_array($ignore) ? $ignore : array_keys($result) as $ignoredValue) {
                    $result[$ignoredValue] = ($result[$ignoredValue] ?? 0) + $collections[$backendId]->getTotal();
                }
            }
        }

        return $result;
    }
}
