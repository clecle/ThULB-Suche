<?php

namespace ThULB\Controller;

use cle\Sip2Wrapper\Sip2Wrapper;
use Laminas\Config\Config;
use Laminas\Http\Response;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use VuFind\Controller\AbstractBase;
use VuFind\Exception\Mail as MailException;
use VuFind\Log\LoggerAwareTrait;
use VuFind\Mailer\Mailer;
use VuFind\Validator\Csrf;
use Whoops\Exception\ErrorException;

/**
 * @method FlashMessenger flashMessenger()
 */
class ILLController extends AbstractBase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected Config $mainConfig;
    protected Config $illConfig;

    protected Csrf $csrfValidator;

    public function __construct(ServiceLocatorInterface $serviceLocator) {
        parent::__construct($serviceLocator);

        $this->mainConfig = $serviceLocator->get('VuFind\Config')->get('config');
        $this->illConfig = $serviceLocator->get('VuFind\Config')->get('thulb')->ILL;

        $this->csrfValidator = $serviceLocator->get(Csrf::class);
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
            'chargeQuantityLimit' => $this->illConfig->chargeQuantityLimit,
        ]);

        try {
            $sip2 = $this->getPicaConnection();
            $view->hasIllAccount = $sip2->startPatronSession($user->username);
            if ($view->hasIllAccount) {
                $view->oldQuantity = $sip2->getPatronFinesTotal() * -1;
            }
        }
        catch (ErrorException | \Exception $e) {
            $this->logError($e);
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
        if(!$request->isPost() || ($view->chargeQuantity = $request->getPost('chargeQuantity', 0)) < 1) {
            return $this->redirect()->toUrl('/ILL/chargecredits');
        }

        $view->cost = $view->chargeQuantity * $this->illConfig->creditPrice;

        try {
            $sip2 = $this->getPicaConnection();
            $view->hasAccount = $sip2->startPatronSession($user->username);

            if($request->getPost('confirmation', false) !== 'true') {
                $view->oldQuantity = $view->hasAccount ? ($sip2->getPatronFinesTotal() * -1) : 0;
                $view->newQuantity = $view->oldQuantity + $view->chargeQuantity;
                $view->oldTotalDue = $this->getTotalDue();
                $view->newTotalDue = $view->oldTotalDue + $view->cost;
                if($view->workrelated = $request->getPost('workrelated', false)) {
                    $view->department = $request->getPost('department', false);
                    $view->facility = $request->getPost('facility', false);
                }
            }
            else {
                if($view->hasAccount && !$request->getPost('workrelated', false)) {
                    $this->chargeIllFee($sip2, $user->username, $view->chargeQuantity, $view->cost);
                }
                else {
                    $this->sendEmail(
                        $this->translate('ill_create_account_subject'),
                        'Email/ill/create_account', [
                            'email' => $user->email,
                            'firstname' => $user->firstname,
                            'lastname' => $user->lastname,
                            'username' => $user->username,
                            'quantity' => $view->chargeQuantity,
                            'cost' => $this->getViewRenderer()->safeMoneyFormat($view->cost)
                        ]
                    );

                    $this->flashMessage('success', 'ill_send_mail_success');
                }

                return $this->redirect()->toUrl('/ILL/chargecredits');
            }
        }
        catch (ErrorException | \Exception $e) {
            $this->logError($e);
            $view->exception = $e;
        }

        return $view;
    }

    public function forgotpasswordAction() : ViewModel {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $request = $this->getRequest();
        if($request->isPost() && $this->doCsrfValidation()) {
            $this->sendEmail(
                $this->translate('ill_forgot_password_subject'),
                'Email/ill/forgot-password', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                ]
            );

            $this->flashMessage('success', 'ill_send_mail_success');
        }

        $view = new ViewModel();
        try {
            $view->hasAccount = $this->getPicaConnection()->startPatronSession($user->username);
        }
        catch (ErrorException | \Exception $e) {
            $this->logError($e);
            $view->exception = $e;
        }

        return $view;
    }

    public function deleteaccountAction() : ViewModel {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $request = $this->getRequest();
        if($request->isPost() && $this->doCsrfValidation()) {
            $this->sendEmail(
                $this->translate('ill_delete_account_subject'),
                'Email/ill/delete-account', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                ]
            );

            $this->flashMessage('success', 'ill_send_mail_success');
        }

        $view = new ViewModel();
        try {
            $view->hasAccount = $this->getPicaConnection()->startPatronSession($user->username);
        }
        catch (ErrorException | \Exception $e) {
            $this->logError($e);
            $view->exception = $e;
        }

        return $view;
    }

    /**
     * @throws \Exception
     */
    protected function getPicaConnection() : Sip2Wrapper {
        $sip2 = new Sip2Wrapper (
            array(
                'hostname' => $this->illConfig->host,
                'port' => $this->illConfig->port,
                'location' => $this->illConfig->location,
                'institutionId' => $this->illConfig->institutionId,
                'withCrc' => true,
                'language' => '001',

                'socket_timeout' => '60',
                'socket_tls_enable' => true,
                'socket_tls_options' => array (
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!SSLv2',
                    'capture_peer_cert' => true,
                    'capture_peer_cert_chain' => true,
                    'disable_compression' => true
                ),

                'maxretry' => 5,
                'debug' => false
            ), true, 'Gossip'
        );

        return $sip2->login($this->illConfig->user, $this->illConfig->pass);
    }

    /**
     * Send the request email.
     *
     * @param string $subject  Subject of the email
     * @param string $template Template to render as content of the email
     * @param array  $data     Data accessed in the email template.
     *
     * @return bool Success of sending the email.
     */
    protected function sendEmail(string $subject, string $template, array $data = []) : bool{
        try {
            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $this->illConfig->departmentEmail,
                $this->mainConfig->Mail->default_from,
                $subject,
                $this->getViewRenderer()->render($template, $data)
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

    protected function sendChargeErrorEmail($cost) {
        $user = $this->getUser();
        $this->sendEmail(
            $this->translate('ill_delete_account_subject'),
            'Email/ill/delete-account', [
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'username' => $user->username,
            ]
        );
        $this->flashMessage('error', 'ill_charge_success.', [
            '%%cost%%' => $this->getViewRenderer()->safeMoneyFormat($cost)
        ]);
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

    protected function chargeIllFee(Sip2Wrapper $sip2, string $username, string $chargeQuantity, string $cost) : bool {
        $sera = $this->serviceLocator->get(\VuFind\ILS\Driver\PluginManager::class)->get('Sera');
        if (!$sera->checkConnection()) {
            throw new ErrorException('SERA connection could not be created.');
        }

        if($sera->chargeIllFee($username, $chargeQuantity, $cost)) {
            try {
                if ($sip2->feePay('14', '00', "$chargeQuantity.00", 'XXX')) {
                    $this->flashMessage('success', 'ill_charge_success', [
                        '%%cost%%' => $this->getViewRenderer()->safeMoneyFormat($cost)
                    ]);
                }
                else {
                    $this->sendChargeErrorEmail($cost);
                }
            }
            catch (\Exception $e) {
                $this->sendChargeErrorEmail($cost);

                throw $e;
            }
        }

        return true;
    }

    protected function flashMessage(string $namespace, string $message, array $tokens = []) {
        $this->flashMessenger()->addMessage([
            'html' => true,
            'msg' => $message,
            'tokens' =>  $tokens,
        ], $namespace);
    }
}
