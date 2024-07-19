<?php
/**
 * View helper for online contents
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
 *
 */

namespace ThULB\View\Helper\Record;

use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;
use VuFind\DoiLinker\PluginManager as DoiLinkerPluginManager;
use VuFind\RecordDriver\DefaultRecord;

class OnlineContent extends AbstractHelper
{
    protected DoiLinkerPluginManager $pluginManager;
    protected array $resolvers;
    protected Config $thulbConfig;

    public function __construct(DoiLinkerPluginManager $pluginManager, string $resolver, Config $thulbConfig) {
        $this->pluginManager = $pluginManager;
        $this->thulbConfig = $thulbConfig;
        $this->resolvers = explode(',', $resolver);
    }

    public function __invoke(DefaultRecord $driver) : array {
        $cleanDoi = $driver->getCleanDOI();
        $doiData = array();
        if($driver->getSourceIdentifier() == 'Summon'
            || ($driver->getSourceIdentifier() == 'Solr' && $driver->isFormat('electronic Article'))) {
            $doiData = $this->doiLookup($cleanDoi);
        }

        $response = array();
        if($data = $this->getFulltextLink($driver, $doiData[$cleanDoi] ?? [])) {
            $response = array_merge($response, $data);
        }
        if($data = $this->getBrowseJournalLink($doiData[$cleanDoi] ?? [])) {
            $response = array_merge($response, $data);
        }
        return $response;
    }

    /**
     * Get the fulltext links for the given record and doi link data.
     *
     * @param DefaultRecord $driver
     * @param array         $doiLinks
     *
     * @return array
     */
    public function getFulltextLink(DefaultRecord $driver, array $doiLinks = []) : array {
        $priorities = array (
            'Solr' => array (
                'Holding' => 'driver',
                'Libkey' => 'doiLinks',
                'Solr' => 'driver'
            ),
            'Summon' => array (
                'Libkey' => 'doiLinks',
                'Holding' => 'driver',
                'DOI' => 'driver'
            )
        );

        foreach($priorities[$driver->getSourceIdentifier()] as $method => $variable) {
            $fullMethodName = sprintf('get%sFulltextLink', $method);
            if($ftLink = $this->$fullMethodName($$variable)) {
                return $ftLink;
            }
        }

        return [];
    }

    /**
     * Get the fulltext link data from the given Solr record data.
     *
     * @param DefaultRecord $driver
     *
     * @return array
     */
    protected function getSolrFulltextLink(DefaultRecord $driver) : array {
        $solrFt = [];

        foreach($driver->tryMethod('getFullTextURL') ?? [] as $fulltextURL) {
            $solrFt[] = array(
                'type' => 'fulltext',
                'label' => 'Full text / PDF',
                'link' => $fulltextURL['url'] ?? $fulltextURL['link'] ?? null,
                'source' => $driver->getSourceIdentifier(),
                'access' => $driver->tryMethod('isOpenAccess') ? 'onlineContent-open' : 'onlineContent-restricted',
                'data' => $fulltextURL
            );
        }

        return $solrFt;
    }

    /**
     * Get the fulltext link data from the given doi link data.
     *
     * @param array $doiLinks
     *
     * @return array
     */
    protected function getLibkeyFulltextLink(array $doiLinks = []) : array {
        // try to get the fulltext link from libkey/browzine
        foreach ($doiLinks as $doiLink) {
            if ($doiLink['data']['fullTextFile'] ?? false) {
                return array (
                    array(
                        'type' => 'fulltext',
                        'label' => 'Full text / PDF',
                        'link' => $doiLink['data']['fullTextFile'],
                        'source' => $doiLink['source'],
                        'access' => $doiLink['data']['openAccess'] ? 'onlineContent-open' : 'onlineContent-restricted',
                        'data' => $doiLink
                    )
                );
            }
        }

        return [];
    }

    /**
     * Get the fulltext link data from the doi.
     *
     * @param DefaultRecord $driver
     *
     * @return array
     */
    protected function getDOIFulltextLink(DefaultRecord $driver) : array {
        $solrFt = [];

        $isFormat = $driver->tryMethod('isFormat', ['eBook|eJournal|electronic Article', true]);

        if ($isFormat && $doi = $driver->getCleanDOI()) {
            $collections = $driver->getIndexField('collection');
            $doiUrl = $this->thulbConfig->DOI->urls['*'] ?? 'https://doi.org/';
            foreach($collections as $collection) {
                if($this->thulbConfig->DOI->urls[$collection] ?? false) {
                    $doiUrl = $this->thulbConfig->DOI->urls[$collection];
                    break;
                }
            }

            $solrFt[] = array(
                'type' => 'fulltext',
                'label' => 'Full text / PDF',
                'link' => $doiUrl . $doi,
                'source' => $driver->getSourceIdentifier(),
                'access' => $driver->tryMethod('isOpenAccess') ? 'onlineContent-open' : 'onlineContent-restricted'
            );
        }

        return $solrFt;
    }

    /**
     * Get the fulltext link data from the holdings of the record.
     * Solr holdings are from the local DAIA and Summon holdings from record data.
     *
     * @param DefaultRecord $driver
     *
     * @return array
     */
    protected function getHoldingFulltextLink(DefaultRecord $driver) : array {
        $holdings = $driver->getHoldings();
        $holdingFt = array ();
        foreach($holdings['holdings']['Remote']['items'] ?? [] as $onlineHolding) {
            $isReference = $driver->getSourceIdentifier() == 'Summon' && !$driver->hasFullText();
            $holdingFt[] = array (
                'type' => $isReference ? 'reference' : 'fulltext',
                'label' => $isReference ? 'get_citation' : 'Full text / PDF',
                'link' => $onlineHolding['remotehref'] ?? null,
                'source' => $driver->getSourceIdentifier() == 'Solr' ?
                        'DAIA' : $driver->getSourceIdentifier(),
                'access' => $isReference ? 'reference' : ($driver->tryMethod('isOpenAccess') ?
                    'onlineContent-open' : 'onlineContent-restricted'),
                'data' => $onlineHolding
            );
        }

        return $holdingFt;
    }

    /**
     * Get the link to browse in the journal from doi link data.
     *
     * @param array $doiLinks
     *
     * @return array
     */
    public function getBrowseJournalLink(array $doiLinks = []) : array {
        foreach($doiLinks as $doiLink) {
            if ($doiLink['data']['browzineWebLink'] ?? false) {
                return array (
                    array (
                        'type' => 'browzine',
                        'label' => 'Browse e-journal',
                        'link' => $doiLink['data']['browzineWebLink'],
                        'source' => $doiLink['source'],
                        'access' => 'browzine',
                        'icon' => $doiLink['icon'],
                        'data' => $doiLink
                    )
                );
            }
        }

        return [];
    }

    /**
     * Look up a DOI in the configured DOI resolvers.
     *
     * @param string $doi
     *
     * @return array
     */
    protected function doiLookup(string $doi) : array {
        $response = array();
        foreach ($this->resolvers as $resolver) {
            if ($this->pluginManager->has($resolver)) {
                $next = $this->pluginManager->get($resolver)->getLinks([$doi]);
                if (empty($response)) {
                    $response = $next;
                } else {
                    foreach ($next as $doi => $data) {
                        if (!isset($response[$doi])) {
                            $response[$doi] = $data;
                        }
                    }
                }
            }
        }

        return $response;
    }
}
