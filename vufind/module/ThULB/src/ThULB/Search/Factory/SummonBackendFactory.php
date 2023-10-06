<?php

/**
 * Factory for Summon backends.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2013.
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
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace ThULB\Search\Factory;

use ThULBSearch\Backend\Summon\Response\RecordCollection;
use VuFind\Search\Factory\SummonBackendFactory as OriginalSummonBackendFactory;
use VuFindSearch\Backend\Summon\Response\RecordCollectionFactory;

/**
 * Factory for Summon backends.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class SummonBackendFactory extends OriginalSummonBackendFactory
{
    /**
     * Create the record collection factory
     *
     * @return RecordCollectionFactory
     */
    protected function createRecordCollectionFactory()
    {
        $manager = $this->serviceLocator
            ->get(\VuFind\RecordDriver\PluginManager::class);
        $stripSnippets = !($this->summonConfig->General->snippets ?? false);
        $callback = function ($data) use ($manager, $stripSnippets) {
            $driver = $manager->get('Summon');
            if ($stripSnippets) {
                unset($data['Snippet']);
            }
            $driver->setRawData($data);
            return $driver;
        };
        return new RecordCollectionFactory($callback, RecordCollection::class);
    }
}
