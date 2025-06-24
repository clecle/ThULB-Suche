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

namespace DHGE\RecordDriver;

use ThULB\RecordDriver\SolrVZGRecord as OriginalSolrVZGRecord;
use File_MARC_Exception;

/**
 * Customized record driver for Records of the Solr index of Verbundzentrale
 * Göttingen (VZG)
 *
 * @category ThULB
 * @package  RecordDrivers
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */

class SolrVZGRecord extends OriginalSolrVZGRecord
{
    const PPN_LINK_ID_PREFIX = 'DE-627';
    const ZDB_LINK_ID_PREFIX = 'DE-600';
    const DNB_LINK_ID_PREFIX = 'DE-101';

    const SEPARATOR = '|\/|';

    /**
     * Return an array of all OnlineHoldings from MARCRecord
     * Field 981: for Links
     * Field 980: for description
     * Field 982:
     *
     * $txt = Text for displaying the link
     * $url = url to OnlineContent
     * $more = further description (PICA 4801)
     * $tmp = ELS-gif for Highlighting ELS Links
     *
     * @return array
     */
    public function getOnlineHoldings() : array
    {
        $retVal = [];

        /* extract all LINKS form MARC 981 */
        $links = $this->getConditionalFieldArray(
            '981', ['1', 'y', 'r', 'w'], true, static::SEPARATOR, ['2' => $this->getLibraryILN()]
        );

        if (!empty($links)){
            /* what kind of LINKS do we have?
             * is there more Information in MARC 980 / 982?
             */
            foreach ($links as $link) {
                $more = '';
                $linkElements = explode(static::SEPARATOR, $link);
                $id = $linkElements[0] ?? '';
                $txt = $linkElements[1] ?? '';
                $url = $linkElements[2] ?? '';

                /* do we have a picture? f.e. ELS-gif */
                if (substr($txt, -3) == 'gif') {
                    $retVal[$id] = $txt;
                    continue;
                }

                /* seems that the real LINK is in 981y if 981r or w is empty... */
                if (empty($txt)) {
                    $txt = $url;
                }
                /* ... and vice versa */
                if (empty($url)) {
                    $url = $txt;
                    $txt = 'fulltext';
                }

                /* Now, we are ready to extract extra-information
                * @details for each link is common catalogisation till RDA-introduction
                */
                $details = $this->getConditionalFieldArray(
                    '980', ['g', 'k'], false, '', ['2' => $this->getLibraryILN(), '1' => $id]
                );

                if (empty($details)) {
                    /* new catalogisation rules with RDA: One Link and single Details for each part */
                    $details = $this->getConditionalFieldArray(
                        '980', ['g', 'k'], false, '', ['2' => $this->getLibraryILN()]);
                }
                if (!empty($details)) {
                    foreach ($details as $detail) {
                        $more .= $detail . "<br>";
                    }
                }

                $corporates = $this->getConditionalFieldArray(
                    '982', ['a'], false, '', ['2' => $this->getLibraryILN(), '1' => $id]
                );

                if (!empty($corporates)) {
                    foreach ($corporates as $corporate) {
                        $more .= $corporate . "<br>";
                    }
                }

                /* extract Info/Links with same ID
                * thats the case, if we have an ELS-gif,
                * so we assume, that the gif is set-up before.
                * f.e.
                * 981 |2 31  |1 00  |w http://kataloge.thulb.uni-jena.de/img_psi/2.0/logos/eLS.gif
                * 981 |2 31  |1 00  |y Volltext  |w http://mybib.thulb.uni-jena.de/els/browser/open/557127483
                */

                // we just need to show host as link-text
                $url_data = parse_url($url);
                $txt_sanitized = $url_data['host'];

                $tmp = (isset($retVal[$id])) ? $retVal[$id] : '';
                $retVal[$id] = $txt_sanitized . static::SEPARATOR .
                    $txt . static::SEPARATOR .
                    $url . static::SEPARATOR .
                    $more . static::SEPARATOR .
                    $tmp;
            }
        }
        else {
            $retVal = "";
        }

        return $retVal;
    }

    /**
     * Get the local classification of the record.
     *
     * @return array
     */
    public function getLocalClassification() : array {
        $fields = $this->getFieldsConditional('983', [
            $this->createFieldCondition('subfield', '2', 'in', $this->getLibraryILN()),
            $this->createFieldCondition('subfield', '8', '==', '00'),
            $this->createFieldCondition('subfield', 'a', '!=', false)
        ]);

        $data = [];
        foreach($fields as $field) {
            $data[] = $this->getMarcReader()->getSubfield($field, 'a');
        }

        return $data;
    }

    /**
     * Get the local subject terms of the record.
     *
     * @return array
     */
    public function getLocalSubjects() : array {
        $fields = $this->getFieldsConditional('982', [
            $this->createFieldCondition('subfield', '2', 'in', $this->getLibraryILN()),
            $this->createFieldCondition('subfield', '1', '==', '00'),
            $this->createFieldCondition('subfield', 'a', '!=', false)
        ]);

        $data = [];
        foreach($fields as $field) {
            $data[] = $this->getMarcReader()->getSubfield($field, 'a');
        }

        return $data;
    }
}
