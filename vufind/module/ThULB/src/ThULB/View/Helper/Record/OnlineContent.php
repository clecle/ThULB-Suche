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
        $doiData = $this->doiLookup($cleanDoi);

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
        $ftLink = array();

        // try to get the fulltext link from libkey/browzine
        foreach($doiLinks as $doiLink) {
            if ($doiLink['data']['fullTextFile'] ?? false) {
                $ftLink = array (
                    'label' => $this->view->transEsc('Full text / PDF'),
                    'link' => $doiLink['data']['fullTextFile'],
                    'source' => $doiLink['source'],
                    'access' => $doiLink['data']['openAccess'] ? 'onlineContent-open' : 'onlineContent-restricted',
                    'class' => $doiLink['data']['openAccess'] ? 'fa fa-unlock-alt' : 'fa fa-lock'
                );

                // stop after finding the full text link
                break;
            }
        }

        // try to get fulltext url from local ILS
        if(!$ftLink && $driver->getSourceIdentifier() == 'Solr') {
            $holdings = $driver->getHoldings();
            foreach($holdings['holdings']['Remote']['items'] ?? [] as $onlineHolding) {
                $ftLink = array(
                    'label' => $this->view->transEsc('Full text / PDF'),
                    'link' => $onlineHolding['remotehref'] ?? null,
                    'source' => 'DAIA',
                    'access' => $driver->tryMethod('isOpenAccess') ? 'onlineContent-open' : 'onlineContent-restricted',
                    'class' => $driver->tryMethod('isOpenAccess') ? 'fa fa-unlock-alt' : 'fa fa-lock'
                );

                // stop after finding the full text link
                break;
            }
        }

        // get fulltext url from the record data
        if(!$ftLink && ($data = $driver->tryMethod('getFullTextURL'))) {
            if($driver->getSourceIdentifier() == 'Summon') {
                $data = array_shift($data);
            }
            $ftLink = array(
                'label' => $this->view->transEsc('Full text / PDF'),
                'link' => $data['url'] ?? $data['link'] ?? null,
                'source' => $driver->getSourceIdentifier(),
                'access' => $driver->tryMethod('isOpenAccess') ? 'onlineContent-open' : 'onlineContent-restricted',
                'class' => $driver->tryMethod('isOpenAccess') ? 'fa fa-unlock-alt' : 'fa fa-lock'
            );
        }

        // get summon reference link if there is no fulltext link
        if(!$ftLink && $driver->getSourceIdentifier() == 'Summon' && ($urls = $driver->getURLs())) {
            $ftLink = array(
                'label' => $urls[0]['desc'],
                'link' => $urls[0]['url'],
                'source' => $driver->getSourceIdentifier(),
                'access' => 'reference'
            );
        }

        return $ftLink;
    }

    public function getBrowseJournalLink($doiLinks = []) {
        $bjLink = array();

        foreach($doiLinks as $doiLink) {
            if ($doiLink['data']['browzineWebLink'] ?? false) {
                $bjLink = array (
                    'label' => $this->view->transEsc('Browse e-journal'),
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
