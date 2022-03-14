<?php
/**
 * View helper for holdings
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
 * @author   Clemes Kynast <clemens.kynast@thulb.uni-jena.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\View\Helper\Record;

use Laminas\View\Helper\AbstractHelper;

class OnlineContent extends AbstractHelper
{
    private $pluginManager;
    private $resolvers;

    public function __construct($pluginManager, $resolver) {
        $this->pluginManager = $pluginManager;
        $this->resolvers = explode(',', $resolver);
    }

    public function __invoke($driver)
    {
        $cleanDoi = $driver->getCleanDOI();
        $doiData = array();
        if($driver->getSourceIdentifier() == 'Summon' ||
            ($driver->getSourceIdentifier() == 'Solr' && $driver->isFormat('eArticle'))) {
            $doiData = $this->doiLookup($cleanDoi);
        }

        $response = array();
        if($data = $this->getFulltextLink($driver, $doiData[$cleanDoi] ?? [])) {
            $response[] = $data;
        }
        if($data = $this->getBrowseJournalLink($doiData[$cleanDoi] ?? [])) {
            $response[] = $data;
        }
        return $response;
    }

    public function getFulltextLink($driver, $doiLinks = []) {
        $priorities = array (
            'Solr' => array (
                'Holding' => 'driver',
                'Libkey' => 'doiLinks',
                'Solr' => 'driver'
            ),
            'Summon' => array (
                'Libkey' => 'doiLinks',
                'Holding' => 'driver',
                'Summon' => 'driver'
            )
        );

        $ftLink = array();
        foreach($priorities[$driver->getSourceIdentifier()] as $method => $variable) {
            $fullMethodName = sprintf('get%sFulltextLink', $method);
            if($ftLink = $this->$fullMethodName($$variable)) {
                break;
            }
        }

        return $ftLink;
    }

    protected function getSolrFulltextLink($driver) {
        $data = $driver->tryMethod('getFullTextURL');

        return !$data ? [] : array (
            'type' => 'main',
            'label' => 'Full text / PDF',
            'link' => $data['url'] ?? $data['link'] ?? null,
            'source' => $driver->getSourceIdentifier(),
//            'access' => $driver->tryMethod('isOpenAccess') ? 'onlineContent-open' : 'onlineContent-restricted',
//            'class' => $driver->tryMethod('isOpenAccess') ? 'fa fa-unlock-alt' : 'fa fa-lock'
        );
    }

    protected function getLibkeyFulltextLink($doiLinks = []) {
        // try to get the fulltext link from libkey/browzine
        foreach ($doiLinks as $doiLink) {
            if ($doiLink['data']['fullTextFile'] ?? false) {
                return array(
                    'type' => 'main',
                    'label' => 'Full text / PDF',
                    'link' => $doiLink['data']['fullTextFile'],
                    'source' => $doiLink['source'],
//                    'access' => $doiLink['data']['openAccess'] ? 'onlineContent-open' : 'onlineContent-restricted',
//                    'class' => $doiLink['data']['openAccess'] ? 'fa fa-unlock-alt' : 'fa fa-lock'
                );
            }
        }

        return [];
    }

    protected function getHoldingFulltextLink($driver) {
        $holdings = $driver->getHoldings();
        foreach($holdings['holdings']['Remote']['items'] ?? [] as $onlineHolding) {
            return array(
                'type' => 'main',
                'label' => $driver->getSourceIdentifier() == 'Summon' && !$driver->hasFullText() ?
                    'get_citation' : 'Full text / PDF',
                'link' => $onlineHolding['remotehref'] ?? null,
                'source' => $driver->getSourceIdentifier() == 'Solr' ?
                        'DAIA' : $driver->getSourceIdentifier(),
//                'access' => $driver->tryMethod('isOpenAccess') ? 'onlineContent-open' : 'onlineContent-restricted',
//                'class' => $driver->tryMethod('isOpenAccess') ? 'fa fa-unlock-alt' : 'fa fa-lock',
                'data' => $onlineHolding
            );
        }

        return [];
    }

    public function getBrowseJournalLink($doiLinks = []) {
        $bjLink = array();

        foreach($doiLinks as $doiLink) {
            if ($doiLink['data']['browzineWebLink'] ?? false) {
                $bjLink = array (
                    'type' => 'browzine',
                    'label' => 'Browse e-journal',
                    'link' => $doiLink['data']['browzineWebLink'],
                    'source' => $doiLink['source'],
                    'access' => 'browzine',
                    'icon' => $doiLink['icon']
                );

                // stop after finding the journal link
                break;
            }
        }

        return $bjLink;
    }

    protected function doiLookup($doi) {
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
