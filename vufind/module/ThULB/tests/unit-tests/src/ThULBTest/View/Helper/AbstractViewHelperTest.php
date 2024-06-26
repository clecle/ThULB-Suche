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

namespace ThULBTest\View\Helper;

use Exception;
use Laminas\Config\Config;
use Laminas\Config\Reader\Ini as IniReader;
use Laminas\Http\Client;
use Laminas\I18n\Translator\Translator;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;
use PHPUnit\Framework\TestCase;
use ThULB\RecordDriver\SolrVZGRecord;
use ThULB\Search\Factory\SolrDefaultBackendFactory;
use VuFind\Search\BackendManager;
use VuFind\View\Helper\Root\Context;
use VuFindTest\Feature\FixtureTrait;
use VuFindTest\Feature\SearchServiceTrait;
use VuFindTest\Feature\ViewTrait;
use VuFindTheme\ThemeInfo;

/**
 * General view helper test class that provides usually used operations.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
abstract class AbstractViewHelperTest extends TestCase
{
    use ViewTrait;
    use FixtureTrait;
    use SearchServiceTrait;

    const FINDEX_REQUEST_PATH = '/31/URMEL/select';
    const FINDEX_QUERY_STRING = '?wt=json&fq=collection_details:((GBV_ILN_31+AND+GBV_KXP)+OR+DBT@UrMEL)+OR+(collection_details:GBV_ILN_2403+AND+isOA_bool:true)&q=id:';

    protected string $translationLocale = 'de';

    protected array $parentThemes = ['root', 'bootstrap'];

    protected string $theme = 'thulb';

    protected ?Config $config = null;
    
    /**
     * Get a working renderer.
     *
     * @param array  $plugins Custom VuFind plug-ins to register
     * @param string $theme   Theme directory to load from
     *
     * @return PhpRenderer
     */
    protected function getPhpRenderer($plugins = [], $theme = null) : PhpRenderer {
        if(!$theme) {
            $theme = $this->theme;
        }

        $resolver = new TemplatePathStack();

        foreach ($this->parentThemes as $parentTheme) {
            $resolver->addPath($this->getPathForTheme($parentTheme));
        }
        $resolver->addPath($this->getPathForTheme($theme));

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        if (!empty($plugins)) {
            $pluginManager = $renderer->getHelperPluginManager();
            foreach ($plugins as $key => $value) {
                $pluginManager->setService($key, $value);
            }
        }
        return $renderer;
    }
    
    /**
     * Query for a record in the index.
     * 
     * @param string $ppn Pica production number of a record
     * @return SolrVZGRecord|null
     * @throws Exception
     */
    protected function getRecordFromFindex(string $ppn) : ?SolrVZGRecord {
        $url = $this->getFindexUrl($ppn);
        $client = new Client($url, array(
            'maxredirects' => 3,
            'timeout' => 10
        ));
        $response = $client->send();
        if ($response->getStatusCode() > 299) {
            throw new Exception("Status code " . $response->getStatusCode() . " for $url.");
        }

        $jsonString = trim($response->getBody());
        $jsonObject = json_decode($jsonString, true);
        $marcObject = new SolrVZGRecord($this->getMainConfig());
        $marcObject->attachSearchService($this->getSearchService($this->getBackendManager()));

        if ($jsonObject['response']['numFound'] < 1) {
            $this->markTestIncomplete("No document found with ppn \"$ppn\" in \"$this->sheetName\" ...");
        }
        try {
            $marcObject->setRawData($jsonObject['response']['docs'][0]);
        } catch (\File_MARC_Exception $e) {
            echo "Record $ppn: " . $e->getMessage() . "\n";
            return null;
        }
        return $marcObject;
    }

    protected function getFindexUrl($ppn) : string {
        return FINDEX_TEST_HOST . static::FINDEX_REQUEST_PATH . static::FINDEX_QUERY_STRING . trim($ppn);
    }

    /**
     * Get a configuration array to turn on first/last setting.
     *
     * @return array
     */
    protected function getFirstLastConfig() : array {
        return ['Record' => ['first_last_navigation' => true]];
    }

    /**
     * Get view helpers needed by test.
     *
     * @param $container
     *
     * @return array
     */
    protected function getViewHelpers($container) : array {
        $context = new Context();
        
        $helpers =  [
//            'auth' => new \VuFind\View\Helper\Root\Auth($this->getMockBuilder('VuFind\Auth\Manager')->disableOriginalConstructor()->getMock()),
            'context' => $context,
            'doi' => new \VuFind\View\Helper\Root\Doi($context),
            'imageLink' => new \VuFindTheme\View\Helper\ImageLink((new ThemeInfo(APPLICATION_PATH . '/themes/', $this->theme))),
            'openUrl' => new \VuFind\View\Helper\Root\OpenUrl($context, [], $this->getMockBuilder('VuFind\Resolver\Driver\PluginManager')->disableOriginalConstructor()->getMock()),
            'proxyUrl' => new \VuFind\View\Helper\Root\ProxyUrl(),
            'record' => new \VuFind\View\Helper\Root\Record(),
            'recordLinker' => new \ThULB\View\Helper\Root\RecordLinker(new \VuFind\Record\Router($this->getMainConfig())),
            'searchMemory' =>$this->getMockBuilder(\VuFind\View\Helper\Root\SearchMemory::class)->disableOriginalConstructor()->getMock(),
            'searchOptions' => new \VuFind\View\Helper\Root\SearchOptions(new \VuFind\Search\Options\PluginManager($container)),
            'searchTabs' => $this->getMockBuilder('VuFind\View\Helper\Root\SearchTabs')->disableOriginalConstructor()->getMock(),
            'transEsc' => new \VuFind\View\Helper\Root\TransEsc(),
            'translate' => new \VuFind\View\Helper\Root\Translate(),
//            'usertags' => new \VuFind\View\Helper\Root\UserTags(),
        ];
        
        $helpers['translate']->setTranslator($this->getTranslator());
        
        return $helpers;
    }
    
    /**
     * Factory for a valid Translator
     */
    protected function getTranslator() : MvcTranslator {
        $translator = new MvcTranslator(new Translator());
        
        $pathStack = [
            APPLICATION_PATH . '/languages',
            LOCAL_OVERRIDE_DIR . '/languages'
        ];
        $fallbackLocales = ['de', 'en'];
        
        $translator->getPluginManager()->setService('ExtendedIni',
                new \VuFind\I18n\Translator\Loader\ExtendedIni(
                    $pathStack, $fallbackLocales
                )
            );
        
        $translator->setLocale($this->translationLocale)
                ->addTranslationFile('ExtendedIni', null, 'default', $this->translationLocale)
                ->addTranslationFile('ExtendedIni', 'Languages', 'Languages', $this->translationLocale)
                ->addTranslationFile('ExtendedIni', 'CreatorRoles', 'CreatorRoles', $this->translationLocale)
                ->addTranslationFile('ExtendedIni', 'DDC', 'DDC', $this->translationLocale);

        return $translator;
    }
    
    /**
     * Define a locale (e.g. 'de' or 'en')
     * 
     * @param string $locale
     */
    protected function setTranslationLocale(string $locale) {
        $this->translationLocale = $locale;
    }
    
    protected function getMainConfig() : Config {
        if (is_null($this->config)) {
            $iniReader = new IniReader();
            $this->config = new Config($iniReader->fromFile(THULB_CONFIG_FILE), true);
        }

        return $this->config;
    }

    /**
     * Given a connector, wrap it up in a backend and backend manager
     *
     * @return BackendManager
     */
    protected function getBackendManager(): BackendManager {
        $container = $this->getMockContainer();
        $backendFactory = new SolrDefaultBackendFactory();
        $backend = $backendFactory($container, 'Solr');
        $container->set('Solr', $backend);
        return new BackendManager($container);
    }
}
