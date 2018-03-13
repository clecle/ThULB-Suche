<?php
/**
 * Override of the VuFind Record link view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  View_Helpers
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace ThULB\View\Helper\Root;
use VuFind\View\Helper\Root\RecordLink as OriginalRecordLink;

/**
 * Description
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class RecordLink extends OriginalRecordLink
{
    /**
     * Given an array representing a related record (which may be a bib ID or OCLC
     * number), this helper renders a URL linking to that record.
     *
     * @param array $link   Link information from record model
     * @param bool  $escape Should we escape the rendered URL?
     *
     * @return string       URL derived from link information
     */
    public function related($link, $escape = true)
    {
        $urlHelper = $this->getView()->plugin('url');
        switch ($link['type']) {
        case 'bib':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=id&jumpto=1';
            break;
        case 'isbn':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=isbn';
            break;
        case 'issn':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=issn';
            break;
        case 'zdb':
        case 'dnb':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=ctrlnum';
            break;
        case 'title':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=title';
            break;
        default:
            throw new \Exception('Unexpected link type: ' . $link['type']);
        }

        $escapeHelper = $this->getView()->plugin('escapeHtml');
        return $escape ? $escapeHelper($url) : $url;
    }
}
