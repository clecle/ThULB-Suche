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

namespace ThULBTest\View\Helper\Root;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Reader\XLSX\Reader;
use Box\Spout\Reader\XLSX\Sheet;
use Exception;
use Interop\Container\Exception\ContainerException;
use Laminas\Router\RouteMatch;
use Throwable;
use ThULB\View\Helper\Root\RecordDataFormatterFactory;
use ThULBTest\View\Helper\AbstractViewHelperTest;
use VuFind\View\Helper\Root\RecordDataFormatter;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;
use VuFindTest\Container\MockContainer;

/**
 * Generalized testing class for the record data formatter view helper. It makes
 * it easy, to add new tests by simple inheritance.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
abstract class AbstractRecordDataFormatterTest extends AbstractViewHelperTest
{   
    const USED_FIELDS_MARKER = 'Genutzte Felder';
    const NAME_DE_MARKER = 'Deutsche Bezeichnung in Vollanzeige';
    const NAME_EN_MARKER = 'Englische Bezeichnung in Vollanzeige';

    /**
     * Provides the name of the sheet of rda.xlsx, that holds the test cases
     * 
     * @var ?string
     */
    protected ?string $sheetName = null;
    
    /**
     * Optional name of the template, that is used by the view helper. This
     * variable needs to be provided, if $recordDriverFunction is not used.
     * 
     * @var ?string
     */
    protected ?string $template = null;
    
    /**
     * Options for the spec builder of the record data formatter
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Optional name of the function of the record driver, that provides the
     * data for the view helper. This variable needs to be provided, if 
     * $template is not used.
     *
     * @var ?string
     */
    protected ?string $recordDriverFunction = null;
    
    /**
     * Key for the metadata that is tested, like it is used in the
     * translation ini files.
     * 
     * @var ?string
     */
    protected ?string $metadataKey = null;
    
    /**
     * Titles for the metadata in different languages. They get extracted from
     * the sheet.
     *
     * @var array
     */
    protected array $metadataTitles = [];

    /**
     * Main testing function. In normal cases, it is enough, to provide either
     * a template path that has to be used by the helper or a record driver
     * function in the derived class variables $template and $recordDriverFunction.
     * If both are needed for the view helper, provide both.
     *
     * In more complex theoretical cases overwrite this function or use other
     * functions "test[...]()" besides this one.
     *
     * @throws Exception|ContainerException|Throwable
     */
    public function testFormatting() {
        $key = is_null($this->metadataKey) ? 'test' : $this->metadataKey;
        
        foreach ($this->getRelevantData() as list($comment, $ppn, $longViewDe, $longViewEn, $shortView, $link)) {
            $record = $this->getRecordFromFindex($ppn);
            $this->setTranslationLocale('de');
            $record->setTranslator($this->getTranslator());
            $formatter = $this->getFormatter();

            $spec = $this->getFormatterSpecBuilder();
            if (!is_null($this->template)) {
                $spec->setTemplateLine(
                    $key,
                    is_null($this->recordDriverFunction) ? true : $this->recordDriverFunction,
                    $this->template,
                    $this->options
                );
            } else if (!is_null($this->recordDriverFunction)) {
                $spec->setLine($this->metadataKey, $this->recordDriverFunction);
            } else {
                $this->markTestSkipped('No information about template or record driver function provided in class  ' . get_class($this));
            }

            $comment = '=== Sheet: ' . $this->sheetName . ', PPN: ' . $ppn . ', DE ===';

            // Test for german metadata presentation:
            $data = $formatter->getData($record, $spec->getArray());
            if(empty($data)) {
                $this->markTestSkipped('No Data to compare found for this record.' . "\n" . $comment);
            }

            $this->assertEquals(
                $this->normalizeUtf8String($longViewDe),
                $this->convertHtmlToString($data[0]['value']),
                $comment
            );
            
            // Test for english metadata presentation:
            if ($longViewEn) {
                $this->setTranslationLocale('en');
                $record->setTranslator($this->getTranslator());
                $formatter = $this->getFormatter();
                $data = $formatter->getData($record, $spec->getArray());
                $comment = '=== Sheet: ' . $this->sheetName . ', PPN: ' . $ppn . ', EN ===';
                $this->assertEquals(
                    $this->normalizeUtf8String($longViewEn),
                    $this->convertHtmlToString($data[0]['value']),
                    $comment
                );
            }
            
            // Test for metadata title in different languages:
            if (!is_null($this->metadataKey) && !empty($this->metadataTitles)) {
                foreach ($this->metadataTitles as $locale => $title) {
                    $this->setTranslationLocale($locale);
                    $viewHelpers = $this->getViewHelpers($this->getMockContainer());
                    $comment = '=== Sheet: ' . $this->sheetName . ', Titel ' . $locale . ' ===';
                    $this->assertEquals(
                        $this->normalizeUtf8String($title),
                        $this->normalizeUtf8String($viewHelpers['translate']($this->metadataKey)),
                        $comment
                    );
                }
            }
        }
    }
    
    /**
     * Transforms the helpers html output to a string, that represents what is
     * shown in the browser.
     * 
     * @param string $helperOutput
     * @return string
     */
    protected function convertHtmlToString(string $helperOutput) : string {
        $htmlLines = explode('<br />', $helperOutput);
        $stringLines = [];
        
        foreach ($htmlLines as $singleLine) {
            $stringLines[] = trim(strip_tags(preg_replace('/\n/', '', $singleLine)));
        }
        
        $string = implode("\n", $stringLines);
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5);
        
        return $this->normalizeUtf8String($string);
    }


    /**
     * German umlaut characters can be represented in different ways and can be seen as
     * different, even if they are normally equal (e.g. 'ä' !== 'ä' in utf8). This function
     * converts these characters to make them equal.
     *
     * @param string $utf8String
     * @return string
     */
    protected function normalizeUtf8String(string $utf8String) : string {
        $output = iconv('UTF-8', 'ASCII//TRANSLIT', $utf8String);
        return preg_replace('/\s{2,}/', ' ', $output);
    }


    /**
     * Extracts the relevant rows from the test cases' spreadsheet. Additionally,
     * it extracts eventually defined metadata titles from the sheet and stores
     * them in the $metadataTitles array.
     *
     * @return array
     *
     * @throws ReaderNotOpenedException|IOException
     */
    protected function getRelevantData() : array {
        $relevantRows = [];
        
        /** @var Sheet $sheet */
        foreach ($this->getSpreadSheetReader()->getSheetIterator() as $sheet) {
            if ($sheet->getName() === $this->sheetName) {
                $isRelevantRow = false;
                /** @var Row $row */
                foreach ($sheet->getRowIterator() as $row) {
                    $rowArray = $row->toArray();
                    if (str_contains($rowArray[0], self::NAME_DE_MARKER)) {
                        $this->metadataTitles['de'] = $rowArray[1];
                    } else if (str_contains($rowArray[0], self::NAME_EN_MARKER)) {
                        $this->metadataTitles['en'] = $rowArray[1];
                    } else if (str_contains($rowArray[0], self::USED_FIELDS_MARKER)) {
                        $isRelevantRow = true;
                        continue;
                    }
                    if ($isRelevantRow) {
                        if (empty($rowArray[0])) {
                            break;
                        }
                        $relevantRows[] = array_slice($rowArray, 0, 6);
                    }
                }
                break;
            }
        }
        if (empty($relevantRows)) {
            $this->markTestSkipped('No sheet found for ' . get_class($this) .
                '. Add it to rda.xlsx and define it in the test class.');
        }
        
        return $relevantRows;
    }

    /**
     * @throws IOException
     */
    protected function getSpreadSheetReader() : Reader {
        $spreadsheetReader = ReaderEntityFactory::createXLSXReader();
        $spreadsheetReader->open(PHPUNIT_FIXTURES_THULB . '/spreadsheet/rda.xlsx');
        
        return $spreadsheetReader;
    }
    
    /**
     * Build a formatter, including necessary mock view w/ helpers.
     *
     * @return RecordDataFormatter
     *
     * @throws ContainerException|Throwable
     */
    protected function getFormatter() : RecordDataFormatter {
        // Build the formatter:
        $factory = new RecordDataFormatterFactory();
        $container = $this->getMockContainer();
        $formatter = $factory($container, RecordDataFormatter::class);

        // Create a view object with a set of helpers:
        $helpers = $this->getViewHelpers($container);
        $view = $this->getPhpRenderer($helpers);

        // Mock out the router to avoid errors:
        $match = new RouteMatch([]);
        $match->setMatchedRouteName('foo');
        $view->plugin('url')
            ->setRouter($this->createMock('Laminas\Router\RouteStackInterface'))
            ->setRouteMatch($match);

        // Inject the view object into all the helpers:
        $formatter->setView($view);
        foreach ($helpers as $helper) {
            $helper->setView($view);
        }

        return $formatter;
    }
    
    protected function getFormatterSpecBuilder() : SpecBuilder {
        return new SpecBuilder();
    }

    protected function getMockContainer() : MockContainer {
        $container = new MockContainer($this);
        $container->set(
            \VuFind\RecordDriver\PluginManager::class,
            new \VuFind\RecordDriver\PluginManager($container)
        );
        $container->set(
            \VuFind\Config\PluginManager::class,
            new \VuFind\Config\PluginManager($container)
        );
        $container->set(
            \VuFind\Config\SearchSpecsReader::class,
            new \VuFind\Config\SearchSpecsReader()
        );
        $container->set(
            'SharedEventManager',
            new \Laminas\EventManager\SharedEventManager()
        );
        $container->set(
            \Laminas\Cache\Service\StorageAdapterFactory::class,
            new \Laminas\Cache\Service\StorageAdapterFactory(
                new \Laminas\Cache\Storage\AdapterPluginManager($container),
                new \Laminas\Cache\Service\StoragePluginFactory(
                    $container->get(\Laminas\Cache\Storage\PluginManager::class)
                )
            )
        );

        return $container;
    }
}
