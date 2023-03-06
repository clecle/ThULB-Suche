<?php
/**
 * Abstract Metadata view helper test class
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

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\XLSX\Reader;
use Laminas\Config\Config;
use Laminas\Config\Reader\Ini as IniReader;
use ThULBTest\View\Helper\Root\AbstractRecordDataFormatterTest as ThULBAbstractRecordDataFormatterTest;

/**
 * Generalized testing class for the record data formatter view helper. It makes
 * it easy, to add new tests by simple inheritance.
 */
abstract class AbstractRecordDataFormatterTest extends ThULBAbstractRecordDataFormatterTest
{
    const FINDEX_QUERY_STRING = '?wt=json&fq=collection_details:(((GBV_ILN_250%20OR%20GBV_ILN_281)%20AND%20GBV_KXP)%20OR%20ZDB-1-BEP%20OR%20ZDB-1-RWF%20OR%20ZDB-1-EFD)&q=id:';

    protected array $parentThemes = ['root', 'bootstrap', 'thulb'];

    protected string $theme = 'dhge';

    protected function getMainConfig() : Config {
        if (is_null($this->config)) {
            $iniReader = new IniReader();
            $this->config = new Config($iniReader->fromFile(DHGE_CONFIG_FILE), true);
        }

        return $this->config;
    }

    /**
     * @throws IOException
     */
    protected function getSpreadSheetReader() : Reader {
        $spreadsheetReader = ReaderEntityFactory::createXLSXReader();
        $spreadsheetReader->open(PHPUNIT_FIXTURES_DHGE . '/spreadsheet/rda.xlsx');

        return $spreadsheetReader;
    }
}
