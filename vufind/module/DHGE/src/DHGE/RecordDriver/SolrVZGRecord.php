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
    const LIBRARY_ILN = ['250', '281'];

    const SEPARATOR = '|\/|';

    /**
     * Get the local classification of the record.
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getLocalClassification() {
        $fields = $this->getFieldsConditional('983', [
            $this->createFieldCondition('subfield', '2', 'in', static::LIBRARY_ILN),
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
     * Return a source for the record.
     *
     * @return string
     */
    public function getSource()
    {
        $source = [];
        if (in_array('KXP', $this->fields['collection'])) {
            foreach(static::LIBRARY_ILN as $iln) {
                if(in_array('GBV_ILN_' . $iln, $this->fields['collection_details'])) {
                    $source['name'] = 'K10plus-Verbundkatalog';
                    $source['url'] = 'https://www.bszgbv.de/services/k10plus/';
                }
            }
            if (!$source && in_array('ISIL_DE-LFER', $this->fields['collection_details'])) {
                $source['name'] = 'Südwestdeutscher Bibliotheksverbund (Lizenzfreie E-Ressourcen)';
            }
        }
        elseif (in_array('DBT@UrMEL', $this->fields['collection'])) {
            $source['name'] = 'Digitale Bibliothek Thüringen (DBT)';
            $source['url'] = 'https://www.db-thueringen.de/content/index.xml';
        }
        elseif (in_array('NL', $this->fields['collection'])) {
            $source['name'] = 'Nationallizenz';
            $source['url'] = 'https://www.nationallizenzen.de/';
        }

        return $source;
    }

    /**
     * Get classification numbers of the record in the "Thüringen-Bibliographie".
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getThuBiblioClassification() {
        // Thuringian bibliography not available for DHGE
        return [];
    }

    /**
     * Checks if the record is part of the "Thüringen-Bibliographie"
     *
     * @return bool
     */
    public function isThuBibliography() {
        // Thuringian bibliography not available for DHGE
        return false;
    }
}
