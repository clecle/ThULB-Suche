<?php
/**
 * Default Controller
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace ThULB\Controller;

use ThULB\Search\Solr\HierarchicalFacetHelper;
use VuFind\Controller\SearchController as OriginalController;
use VuFind\Search\Results\PluginManager as ResultsPluginManager;
use VuFindSearch\Backend\Exception\BackendException;
use Laminas\View\Model\ViewModel;

/**
 * Redirects the user to the appropriate default VuFind action.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class SearchController extends OriginalController
{
    use FormatSearchTrait;


    public function historyAction()
    {
        if (!$this->getAuthManager()->getUserObject()) {
            return $this->forceLogin();
        }

        return parent::historyAction();
    }

    /**
     * Returns a list of all items associated with one facet for the lightbox
     *
     * Parameters:
     * facet        The facet to retrieve
     * searchParams Facet search params from $results->getUrlQuery()->getParams()
     *
     * @return ViewModel
     */
    public function facetListAction() : ViewModel {
        $view = parent::facetListAction();

        $list = $view->getVariable('data');
        $facet = $view->getVariable('facet');
        $results = $view->getVariable('results');

        if(in_array($facet, $results->getOptions()->getHierarchicalFacets())) {
            // format list as hierarchical facet
            $hierarchicalFacetHelper = $this->serviceLocator->get(HierarchicalFacetHelper::class);
            $facetArray = $hierarchicalFacetHelper->buildFacetArray(
                $facet, $list, $results->getUrlQuery(), true, $results
            );
            $list = $hierarchicalFacetHelper->flattenFacetHierarchy($facetArray);
        }
        elseif($view->getVariable('sort') == 'index') {
            // only sort by display text if the list is sorted by index(alphabetically)
            usort($list, function ($facet1, $facet2) {
                return strcasecmp($facet1['displayText'], $facet2['displayText']);
            });
        }

        $view->setVariable('data', $list);

        $vars = $view->getVariables();
        unset($vars['delegateParams']);
        $view->setVariable('delegateParams', $vars);

        return $view;
    }

    /**
     * Results action.
     *
     * @return mixed
     */
    public function resultsAction()
    {
        // Check permission to avoid an error with debug mode
        $this->plugin('permission')->check('hide.VpnWarning', false);

        try {
            // Remove emojis from lookfor to avoid solr errors
            $originalLookFor = trim($this->params()->fromQuery('lookfor', ''));
            $formattedLookFor = trim($this->formatLookFor($originalLookFor));
            if (!empty($formattedLookFor)) {
                $this->getRequest()->getQuery()->set('lookfor', $formattedLookFor);
            }

            $view = parent::resultsAction();
        }
        catch (BackendException $e) {
            // An error occurred in the backend, create an empty result list and forward the exception to the template
            $resultsManager = $this->serviceLocator->get(ResultsPluginManager::class);
            $view = $this->createViewModel();
            $view->results = $resultsManager->get('EmptySet');
            $view->params  = $resultsManager->get($this->searchClassId)->getParams();
            $view->exception = $e;
        }

        $view->lookfor = $originalLookFor;

        return $view;
    }
}
