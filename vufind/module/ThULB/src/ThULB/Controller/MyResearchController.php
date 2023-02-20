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

use IOException;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mime\Message;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use Picqer\Barcode\BarcodeGeneratorPNG;
use ThULB\PDF\LetterOfAuthorization;
use VuFind\Controller\MyResearchController as OriginalController;
use Laminas\Mvc\MvcEvent;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use VuFind\Exception\Mail as MailException;
use VuFind\Http\PhpEnvironment\Request;
use VuFind\Log\LoggerAwareTrait;
use VuFind\Mailer\Mailer;
use Whoops\Exception\ErrorException;


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

    protected $mainConfig;

    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);

        $this->mainConfig = $sm->get('VuFind\Config')->get('config');
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

        // Get the current renewal status and process renewal form, if necessary:
        $renewStatus = $catalog->checkFunction('Renewals', compact('patron'));
        $renewResult = $renewStatus
            ? $this->renewals()->processRenewals(
                $this->getRequest()->getPost(), $catalog, $patron
            )
            : [];

        // We always want to display a renewal form:
        $renewForm = false;

        // Get checked out item details:
        $result = $catalog->getMyProvidedItems($patron);

        // Get page size:
        $config = $this->getConfig();
        $limit = isset($config->Catalog->checked_out_page_size)
            ? $config->Catalog->checked_out_page_size : 50;

        // Build paginator if needed:
        if ($limit > 0 && $limit < count($result)) {
            $adapter = new ArrayAdapter($result);
            $paginator = new Paginator($adapter);
            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($this->params()->fromQuery('page', 1));
            $pageStart = $paginator->getAbsoluteItemNumber(1) - 1;
            $pageEnd = $paginator->getAbsoluteItemNumber($limit) - 1;
        } else {
            $paginator = false;
            $pageStart = 0;
            $pageEnd = count($result);
        }

        $transactions = $hiddenTransactions = [];
        foreach ($result as $i => $current) {
            // Add renewal details if appropriate:
            $current = $this->renewals()->addRenewDetails(
                $catalog, $current, $renewStatus
            );

            // Build record driver (only for the current visible page):
            if ($i >= $pageStart && $i <= $pageEnd) {
                $transactions[] = $this->ilsRecords()->getDrivers([$current])[0];
            } else {
                $hiddenTransactions[] = $current;
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
        
        if (!$this->getAuthManager()->isLoggedIn()) {
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
                && $this->getAuthManager()->isLoggedIn()) {

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
        if($this->getAuthManager()->isLoggedIn()) {
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

        if($this->getAuthManager()->isLoggedIn()) {
            $pw = $this->getAuthManager()->getIdentity()->getCatPassword();
            if (!$this->getAuthManager()->validatePasswordAgainstPolicy($pw)) {
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

    /**
     * Process the creation of a letter of authority for the user.
     *
     * @return ViewModel
     *
     * @throws IOException
     */
    public function letterOfAuthorizationAction() : ViewModel {
        if (!$this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        }

        $savePath = $this->mainConfig->LetterOfAuthorization->pdf_save_path;
        if ((!file_exists($savePath) && !mkdir($savePath)) || !is_readable($savePath) || !is_writable($savePath)) {
            throw new IOException('File not writable: "' . $savePath . '"');
        }

        $errors = [];
        $request = $this->getRequest();
        $user = $this->getUser();
        $ilsUser = $this->getILS()->getMyProfile($this->catalogLogin());
        if ($ilsUser['statuscode'] == 6) {
            $this->flashMessenger()->addErrorMessage('you are not allowed to issue a letter of authorization');
        }
        elseif ($request->isPost() && !$request->getPost('mylang')) {
            // validate form
            $requiredFields = ['firstname', 'lastname', 'grantUntil', 'check1', 'check2'];
            foreach ($requiredFields as $field) {
                if (!$request->getPost($field)) {
                    $errors[] = $field;
                }
            }
            if($errors) {
                $this->flashMessenger()->addErrorMessage('fields with asterisk are required');
            }
            if(($grantUntil = $request->getPost('grantUntil')) && $grantUntil < date('Y-m-d')) {
                $this->flashMessenger()->addErrorMessage('date must be in the future');
                $errors[] = 'grantUntil';
            }

            $fileName = sprintf('%s_%s.pdf', $user['username'], time());
            if(!$errors && $this->createLetterOfAuthorizationPDF($request, $fileName, $ilsUser) &&
                    $this->sendLetterOfAuthorizationEmail($user['firstname'] . ' ' . $user['lastname'], $user['email'], $fileName)) {
                $this->flashMessenger()->addSuccessMessage('Letter of Authorization was sent to your email address');
            }
            else {
                $this->flashMessenger()->addErrorMessage('Letter of Authorization could not be sent to your email address');
            }
        }

        return $this->createViewModel(['request' => $this->request, 'errors' => $errors]);
    }

    /**
     * Create the pdf of a letter of authorization.
     *
     * @param Request $request  Request with the form data
     * @param string $fileName  Name to save the pdf with.
     * @param string[] $ilsUser Userdata from ILS
     *
     * @return bool
     */
    protected function createLetterOfAuthorizationPDF(Request $request, string $fileName, array $ilsUser) : bool {
        try {
            $user = $this->getUser();

            if(!file_exists(LOCAL_CACHE_DIR . '/barcode')) {
                mkdir(LOCAL_CACHE_DIR . '/barcode');
            }

            $barcodeFile = LOCAL_CACHE_DIR . '/barcode/' . $user['username'] . '.png';
            if(!file_exists($barcodeFile)) {
                $generator = new BarcodeGeneratorPNG();
                file_put_contents($barcodeFile, $generator->getBarcode($user['username'], $generator::TYPE_CODE_39, 1));
            }

            $savePath = $this->mainConfig->LetterOfAuthorization->pdf_save_path;

            $pdf = new LetterOfAuthorization($this->getViewRenderer()->plugin('translate'));
            $pdf->setBarcode($barcodeFile);
            $pdf->setAuthorizedName($request->getPost('firstname') . ' ' . $request->getPost('lastname'));
            $pdf->setGrantedUntil(date('d.m.Y', strtotime($request->getPost('grantUntil'))));
            $pdf->setIssuerEMail($user['email']);
            $pdf->setIssuerName($user['firstname'] . ' ' . $user['lastname']);
            $pdf->setIssuerUserNumber($user['username']);
            $pdf->setIssuerAddress(explode(',', $ilsUser['address1'] ?? ''));

            $pdf->create();
            $pdf->Output('F', $savePath . $fileName);
        }
        catch (ErrorException $e) {
            if($this->logger != null && is_callable($this->logger, 'logException')) {
                $this->logger->logException($e, $this->getEvent()->getRequest()->getServer());
            }

            return false;
        }

        return true;
    }

    /**
     * Send an email with the attached letter of authorization pdf to the user.
     *
     * @param string $name        Name of the user, shown in email
     * @param string $email       Email address of the user
     * @param string $pdfFilename Name of the pdf file
     *
     * @return bool
     */
    protected function sendLetterOfAuthorizationEmail(string $name, string $email, string $pdfFilename) : bool {
        try {
            // @todo translate email
            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->setContent(htmlspecialchars_decode(
                $this->getViewRenderer()->render('Email/letter-of-authorization', [
                    'name' => $name,
                ])
            ));

            $savePath = $this->mainConfig->LetterOfAuthorization->pdf_save_path;

            $fileContent = file_get_contents($savePath . $pdfFilename, 'r');
            $attachment = new Part($fileContent);
            $attachment->type = 'application/pdf';
            $attachment->encoding = Mime::ENCODING_BASE64;
            $attachment->filename = $pdfFilename;
            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;

            // then add them to a MIME message
            $mimeMessage = new Message();
            $mimeMessage->setParts(array($text, $attachment));

            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $email,
                $this->mainConfig->Mail->default_from,
                $this->translate('Email::letter_of_authorization_email_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            if($this->logger != null && is_callable($this->logger, 'logException')) {
                $this->logger->logException($e, $this->getEvent()->getRequest()->getServer());
            }

            return false;
        }

        return true;
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
