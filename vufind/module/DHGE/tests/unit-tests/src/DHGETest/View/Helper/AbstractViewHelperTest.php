<?php
/**
 * Abstract view helper test class
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace DHGETest\View\Helper;

use Laminas\Config\Config;
use Laminas\Config\Reader\Ini as IniReader;

/**
 * General view helper test class that provides usually used operations.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
abstract class AbstractViewHelperTest extends \ThULBTest\View\Helper\AbstractViewHelperTest
{
    const FINDEX_QUERY_STRING = '?wt=json&fq=collection_details:(((GBV_ILN_250%20OR%20GBV_ILN_281)%20AND%20GBV_KXP)%20OR%20ZDB-1-BEP%20OR%20ZDB-1-RWF%20OR%20ZDB-1-EFD)&q=id:';

    /**
     * Get a working renderer.
     *
     * @param array  $plugins Custom VuFind plug-ins to register
     * @param string $theme   Theme directory to load from
     *
     * @return \Laminas\View\Renderer\PhpRenderer
     */
    protected function getPhpRenderer($plugins = [], $theme = 'dhge')
    {
        $resolver = new \Laminas\View\Resolver\TemplatePathStack();

        $resolver->setPaths(
            [
                $this->getPathForTheme('root'),
                $this->getPathForTheme('bootstrap3'),
                $this->getPathForTheme('thulb'),
                $this->getPathForTheme($theme)
            ]
        );
        $renderer = new \Laminas\View\Renderer\PhpRenderer();
        $renderer->setResolver($resolver);
        if (!empty($plugins)) {
            $pluginManager = $renderer->getHelperPluginManager();
            foreach ($plugins as $key => $value) {
                $pluginManager->setService($key, $value);
            }
        }
        return $renderer;
    }

    protected function getFindexUrl($ppn) {
        return FINDEX_TEST_HOST . self::FINDEX_REQUEST_PATH . self::FINDEX_QUERY_STRING . trim($ppn);
    }

    protected function getMainConfig()
    {
        if (is_null($this->config)) {
            $iniReader = new IniReader();
            $this->config = new Config($iniReader->fromFile(DHGE_CONFIG_FILE), true);
        }

        return $this->config;
    }
}
