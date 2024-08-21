<?php

namespace ThULB\Controller;

use Laminas\Config\Config;
use Laminas\Http\Response;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Log\Writer\Noop;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Container as SessionContainer;
use Laminas\View\Model\ViewModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ThULB\Log\LoggerAwareTrait;
use VuFind\Controller\AbstractBase;
use VuFind\Exception\Mail as MailException;
use VuFind\Log\Logger;
use VuFind\Log\Writer\Stream;
use VuFind\Mailer\Mailer;
use VuFind\Validator\SessionCsrf;
use Whoops\Exception\ErrorException;

/**
 * @method FlashMessenger flashMessenger()
 */
class ILLController extends AbstractBase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected Config $mainConfig;
    protected Config $illConfig;

    protected Logger $illLogger;
    protected array  $logContent = array ();

    protected SessionCsrf $csrfValidator;

    public function __construct(ServiceLocatorInterface $serviceLocator) {
        parent::__construct($serviceLocator);

        $this->mainConfig = $serviceLocator->get('VuFind\Config')->get('config');
        $this->illConfig = $serviceLocator->get('VuFind\Config')->get('thulb')->ILL;

        $this->csrfValidator = $serviceLocator->get(SessionCsrf::class);

        $this->illLogger = new Logger($serviceLocator->get(\VuFind\Net\UserIpReader::class));
        if(($file = $this->illConfig->logFile ?? false) && is_file($file) && is_writeable($file)) {
            $this->illLogger->addWriter(new Stream($file));
        }
        else {
            $this->illLogger->addWriter(new Noop());
        }
    }

    public function onDispatch(MvcEvent $e) : ViewModel|MvcEvent|Response {
        if (!isset($this->illConfig->enabled) || !$this->illConfig->enabled) {
            $this->flashMessage('error', 'Feature disabled');
            return $this->redirect()->toUrl('/');
        }

        return parent::onDispatch($e);
    }

    public function chargecreditsAction() : ViewModel {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $view = new ViewModel([
            'hasIllAccount'       => false,
            'oldQuantity'         => 0,
            'creditPrice'         => $this->illConfig->creditPrice,
            'chargeQuantityLimit' => $this->getChargeQuantityLimit(),
        ]);

        try {
            $illInformation = $this->getIllInformation($user->username);
            $view->hasIllAccount = !empty($illInformation);
            $view->oldQuantity = $illInformation['balance'] ?? 0;
        }
        catch (ErrorException | \Exception $e) {
            $this->logException($e);
            $view->exception = $e;
        }

        return $view;
    }

    public function chargeconfirmationAction() : ViewModel | Response {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $view = new ViewModel();

        $request = $this->getRequest();
        if(!$request->isPost()
            || !$this->doCsrfValidation()
            || !$request->getPost('submitButton', false)
            || ($view->chargeQuantity = $request->getPost('chargeQuantity', 0)) < 1)
        {
            if($request->getQuery('layout', false) == 'lightbox') {
                return (new ViewModel())->setTemplate('Helpers/flashMessages.phtml');
            }
            else {
                return $this->redirect()->toUrl('/ILL/chargecredits');
            }
        }

        $view->cost = $view->chargeQuantity * $this->illConfig->creditPrice;
        $this->addLogContent('Username: ' . $user->username);

        try {
            $illInformation = $this->getIllInformation($user->username);
            $this->addLogContent('Get ILL Information... Success.');
        }
        catch (ErrorException | \Exception $e) {
            $this->logException($e);
            $view->exception = $e;

            $this->addLogContent('Get ILL Information... Failed.');

            return $view;
        }

        $view->hasAccount = !empty($illInformation);

        if ($request->getPost('workrelated', false)) {
            $this->addLogContent('Request type: work related');

            if(($department = $request->getPost('department', false))
                && ($facility = $request->getPost('facility', false)))
            {
                $this->sendEmail(
                    'Antrag auf dienstliches Fernleihguthaben',
                    'Email/ill/work-related', [
                        'email' => $user->email,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'username' => $user->username,
                        'quantity' => $view->chargeQuantity,
                        'department' => $department,
                        'facility' => $facility,
                    ]
                );
            }
            else {
                $this->addLogContent('Error: department or facility not provided');

                $this->flashMessage('error', 'ill_charge_no_department_or_facility');
                return (new ViewModel())->setTemplate('Helpers/flashMessages.phtml');
            }
        }
        elseif(!$view->hasAccount) {
            $this->addLogContent('Request type: create account');

            $this->sendEmail(
                'Antrag auf Einrichtung eines Fernleihkontos',
                'Email/ill/create-account', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                    'quantity' => $view->chargeQuantity,
                    'cost' => $this->getViewRenderer()->safeMoneyFormat($view->cost)
                ]
            );
        }
        elseif($request->getPost('confirmation', false) !== 'true') {
            $this->addLogContent('Request type: confirmation');

            $view->newQuantity = ($illInformation['balance'] ?? 0) + $view->chargeQuantity;
            $view->oldTotalDue = $this->getTotalDue();
            $view->newTotalDue = $view->oldTotalDue + $view->cost;
            if($view->workrelated = $request->getPost('workrelated', false)) {
                $view->department = $request->getPost('department', false);
                $view->facility = $request->getPost('facility', false);
            }
        }
        else {
            $this->addLogContent('Request type: direct charge');
            $ilsPM = $this->serviceLocator->get(\VuFind\ILS\Driver\PluginManager::class);

            if($reqId = $ilsPM->get('Sera')->chargeIllFee($user->username, $view->chargeQuantity, $view->cost)) {
                $this->addLogContent('SERA API... Success.', [
                    'username' => $user->username,
                    'quantity' => $view->chargeQuantity,
                    'cost' => $view->cost,
                    'requisition id' => $reqId,
                ]);

                try {
                    $ilsPM->get('cbsuserdpo')->chargeCredits($user->username, $view->chargeQuantity);
                    $this->addLogContent('UserDPO API... Success.', [
                        'username' => $user->username,
                        'quantity' => $view->chargeQuantity,
                    ]);

                    $this->flashMessage('success', 'ill_charge_success', [
                        '%%cost%%' => $this->getViewRenderer()->safeMoneyFormat($view->cost)
                    ]);
                }
                catch (\Exception $e) {
                    $this->addLogContent('UserDPO API... Failed.');
                    $this->logException($e);
                    $this->sendChargeErrorEmail($view->chargeQuantity, $view->cost);

                    $view->exception = $e;
                    $view->chargeException = true;
                }
            }
            else {
                $this->addLogContent('SERA API... Failed.', [
                    'username' => $user->username,
                    'quantity' => $view->chargeQuantity,
                    'cost' => $view->cost
                ]);
                $this->addLogContent('Abort charging. Request user to try again later.');
                $this->flashMessage('error', 'An error occurred during execution; please try again later.');
                return (new ViewModel())->setTemplate('Helpers/flashMessages.phtml');
            }
        }

        $this->writeIllLog();

        return $view;
    }

    public function forgotpasswordAction() : ViewModel {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $request = $this->getRequest();
        if($request->isPost() && $request->getPost('forgot-password', false) && $this->doCsrfValidation()) {
            $this->addLogContent('Request type: forgot password');
            $this->sendEmail(
                'Fernleihpasswort',
                'Email/ill/forgot-password', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                ]
            );

            $this->writeIllLog();
        }

        return new ViewModel();
    }

    public function deleteaccountAction() : ViewModel {
        // Force login if necessary:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }

        return new ViewModel();
    }

    public function deleteaccountconfirmationAction () : ViewModel {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $request = $this->getRequest();
        if($request->isPost() && $this->doCsrfValidation() && $request->getPost('confirmation', false)) {
            $this->addLogContent('Request type: delete account');
            $this->sendEmail(
                'Fernleihkonto löschen',
                'Email/ill/delete-account', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                ]
            );

            $this->writeIllLog();
        }

        return new ViewModel();
    }

    public function getIllInformation(string $username) : array {
        try {
            $userInfo = $this->serviceLocator->get(\VuFind\ILS\Driver\PluginManager::class)
                ->get('cbsuserdpo')->getUserInformation($username);
        }
        catch (ErrorException $e) {
            if($e->getCode() == '404') {
                // username not found
                return [];
            }

            throw $e;
        }

        return $userInfo;
    }

    /**
     * Send the request email.
     *
     * @param string $subject  Subject of the email
     * @param string $template Template to render as content of the email
     * @param array  $data     Data accessed in the email template.
     *
     * @return bool Success of sending the email.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendEmail(string $subject, string $template, array $data = [], $showMessage = true) : bool {
        $subject = $subject . ' (' . strtoupper($this->serviceLocator->get('VuFind\Translator')->getLocale()) . ')';
        $success = false;

        $data = array_merge(['subject' => $subject], $data);

        try {
            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $this->illConfig->departmentEmail,
                $this->mainConfig->Mail->default_from,
                $subject,
                $this->getViewRenderer()->render($template, $data)
            );

            if($showMessage) {
                $this->flashMessage('success', 'ill_send_mail_success');
            }

            $success = true;
        }
        catch (MailException $e) {
            $this->logException($e);

            if($showMessage) {
                $this->flashMessage('error', 'An error occurred during execution; please try again later.');
            }
        }

        $this->addLogContent('Send mail ... ' . ($success ? 'OK' : 'Failed') . '.', $data);

        return $success;
    }

    protected function sendChargeErrorEmail(string $quantity, string $cost) : bool {
        $user = $this->getUser();
        $success = $this->sendEmail(
            'Fehler beim Buchen von Fernleihguthaben',
            'Email/ill/charge-error', [
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'username' => $user->username,
                'quantity' => $quantity,
                'cost' => $cost
            ],
            false
        );

        if(!$success) {
            $this->logError('ChargeErrorMail could not be sent.', [
                'username' => $user->username
            ]);
        }

        return $success;
    }

    protected function getTotalDue() : int {
        $totalDue = 0;
        $patron = $this->catalogLogin();
        if (is_array($patron)) {
            $catalog = $this->getILS();
            $fines = $catalog->getMyFines($patron);

            foreach ($fines as $fine) {
                $totalDue += $fine['balance'] ?? 0;
            }
        }

        return $totalDue / 100;
    }

    /**
     * Returns charge quantity limit based on user type.
     *
     * @return int
     */
    protected function getChargeQuantityLimit() : int {
        try {
            $paiaSession = new SessionContainer('PAIA', $this->serviceLocator->get(\Laminas\Session\SessionManager::class));

            return match ((int)$paiaSession->userType ?? -1) {
                3, 4, 5, 6, 8 => $this->illConfig->employeeChargeQuantityLimit,
                default => $this->illConfig->chargeQuantityLimit
            };
        }
        catch (ContainerExceptionInterface | NotFoundExceptionInterface $ignore) {}

        return $this->illConfig->chargeQuantityLimit;
    }

    protected function doCsrfValidation() : bool {
        if (!$this->csrfValidator->isValid($this->getRequest()->getPost('csrf'))) {
            $this->flashMessenger()->addErrorMessage('csrf_validation_failed');
            return false;
        }
        // After successful token verification, clear list to shrink session
        // and prevent double submit:
        $this->csrfValidator->trimTokenList(0);

        return true;
    }

    protected function flashMessage(string $namespace, string $message, array $tokens = []) : void {
        $this->flashMessenger()->addMessage([
            'html' => true,
            'msg' => $message,
            'tokens' =>  $tokens,
        ], $namespace);
    }

    protected function addLogContent(string $content, array $additionalData = []) {
        if(!$additionalData) {
            $this->logContent[] = $content;
        }
        else {
            $this->logContent[] = array (
                'msg' => $content,
                'data' => $additionalData
            );
        }
    }

    protected function writeIllLog() {
        $this->illLogger->log(Logger::INFO, var_export($this->logContent, true));
    }
}
