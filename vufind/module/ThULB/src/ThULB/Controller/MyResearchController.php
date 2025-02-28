<?php
/**
 * Override of the VuFind MyResearch Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2015.
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

namespace ThULB\Controller;

use Laminas\Config\Config;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use ThULB\Log\LoggerAwareTrait;
use VuFind\Controller\MyResearchController as OriginalController;

/**
 * Controller for the user account area.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class MyResearchController extends OriginalController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    use ChangePasswordTrait {
        onDispatch as public trait_onDispatch;
    }

    protected Config $mainConfig;

    public function __construct(
        ServiceLocatorInterface $sm,
        Container $container,
        \VuFind\Config\PluginManager $configLoader,
        \VuFind\Export $export
    ) {
        parent::__construct($sm, $container, $configLoader, $export);

        $this->mainConfig = $configLoader->get('config');
    }

    /**
     * User login action -- clear any previous follow-up information prior to
     * triggering a login process. This is used for explicit login links within
     * the UI to differentiate them from contextual login links that are triggered
     * by attempting to access protected actions.
     *
     * @return mixed
     */
    public function userloginAction()
    {
        $return = parent::userloginAction();
        $this->clearFollowupUrl();

        return $return;
    }
    
    /**
     * Send list of checked out books to view
     *
     * @return ViewModel
     */
    public function checkedoutAction() : ViewModel
    {
        $viewModel = parent::checkedoutAction();
        $viewModel->setVariable('renewForm', true);

        return $viewModel;
    }
    
    /**
     * We don't use this action anymore; it is replaced by the loans action, that
     * combines all items held by the patron and all provided items
     *
     * @return mixed
     */
    public function storageRetrievalRequestsAction()
    {
        return $this->redirect()->toRoute('default', ['controller' => 'holds', 'action' => 'holdsAndSRR']);
    }

    /**
     * Send list of books that are provided for the user to view
     *
     * @return ViewModel
     */
    public function providedAction() : ViewModel
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Display account blocks, if any:
        $this->addAccountBlocksToFlashMessenger($catalog, $patron);

        // renewal not possible here
        $renewResult = [];
        $renewForm = false;

        // Get paging setup:
        $config = $this->getConfig();
        $pageSize = $config->Catalog->checked_out_page_size ?? 50;
        $pageOptions = $this->getPaginationHelper()->getOptions(
            (int)$this->params()->fromQuery('page', 1),
            $this->params()->fromQuery('sort'),
            $pageSize,
            $catalog->checkFunction('getMyTransactions', $patron)
        );

        // Get checked out item details:
        $result = $catalog->getMyProvidedItems($patron);

        // Build paginator if needed:
        $resultCount = count($result);
        $paginator = $this->getPaginationHelper()->getPaginator(
            $pageOptions,
            $resultCount,
            $result
        );
        if ($paginator) {
            $pageStart = $paginator->getAbsoluteItemNumber(1) - 1;
            $pageEnd = $paginator->getAbsoluteItemNumber($pageOptions['limit']) - 1;
        } else {
            $pageStart = 0;
            $pageEnd = $resultCount;
        }

        $transactions = $hiddenTransactions = [];
        foreach ($result as $i => $current) {
            // Build record driver (only for the current visible page):
            if ($i >= $pageStart && $i <= $pageEnd) {
                $transactions[] = $this->ilsRecords()->getDrivers([$current])[0];
            }
        }

        return $this->createViewModel(
            compact(
                'transactions', 'renewForm', 'renewResult', 'paginator',
                'hiddenTransactions'
            )
        );
    }

    /**
     * Provide a link to the password change site of the ILS.
     *
     * @return ViewModel
     */
    public function changePasswordLinkAction() : ViewModel
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }
        
        if (!$this->getAuthManager()->getUserObject()) {
            return $this->forceLogin();
        }
        
        $view = $this->createViewModel($this->params()->fromPost());
        
        $view->setTemplate('myresearch/ilsaccountlink');
        return $view;
    }

    /**
     * Catalog Login Action
     *
     * @return mixed
     */
    public function catalogloginAction()
    {
        return $this->forwardTo('MyResearch', 'Login');
    }

    /**
     * Execute the request.
     * Logout logged in users if the ILS Driver switched to an offline mode and redirect to login screen.
     *
     * @param  MvcEvent $event
     *
     * @return mixed
     */
    public function onDispatch(MvcEvent $event)
    {
        $routeName = 'myresearch-userlogin';
        if($this->getILS()->getOfflineMode()
                && strtolower($event->getRouteMatch()->getMatchedRouteName()) !== $routeName
                && $this->getAuthManager()->getUserObject()) {

            $event->getRouteMatch()->setParam('action', 'logout');
            parent::onDispatch($event);

            return $this->redirect()->toRoute($routeName);
        }

        return $this->trait_onDispatch($event);
    }

    /**
     * Send user's saved favorites from a particular list to the view
     *
     * @return mixed
     */
    public function mylistAction()
    {
        if($this->getAuthManager()->getUserObject()) {
            $this->flashMessenger()->addMessage(
                array(
                    'html' => true,
                    'msg' => 'favorites_questions',
                    'tokens' => ['%%address%%' => $this->getConfig()->Site->email]
                ), 'warning'
            );
        }

        return parent::mylistAction();
    }

    /**
     * Handling submission of a new password for a user.
     *
     * @return ViewModel
     */
    public function changePasswordAction() : ViewModel {
        /* @var $view ViewModel */
        $view =  parent::changePasswordAction();

        if($this->getAuthManager()->getUserObject()) {
            if (!$this->getAuthManager()->validatePasswordAgainstPolicy()) {
                $this->layout()->setVariable('showBreadcrumbs', false);
                $this->layout()->setVariable('searchbox', false);
                $view->setVariable('forced', true);

                $this->flashMessenger()->addMessage('force new PW', 'error');
            }
        }

        return $view;
    }

    /**
     * Handling submission of a new password for a user.
     *
     * @return ViewModel
     */
    public function newPasswordAction() : ViewModel | Response
    {
        $view = parent::newPasswordAction();

        $fm = $this->flashMessenger();
        if($fm->hasCurrentSuccessMessages()) {
            $messages = $fm->getCurrentSuccessMessages();
            $fm->clearCurrentMessagesFromNamespace('success');

            foreach ($messages as $message) {
                if(!is_array($message)) {
                    $message = ['msg' => $message];
                }

                $message['dataset']['lightbox-ignore'] = true;
                $fm->addSuccessMessage($message);
            }
        }

        return $view;
    }

    public function profileAction() {
        $view = parent::profileAction();

        $patron = $this->catalogLogin();
        if (is_array($patron)) {
            $catalog = $this->getILS();
            $fines = $catalog->getMyFines($patron);

            $totalDue = 0;
            foreach ($fines as $fine) {
                $totalDue += $fine['balance'] ?? 0;
            }
            $view->totalDue = $totalDue;
        }

        return $view;
    }

}
