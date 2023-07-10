<?php

namespace ThULB\Controller;

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
            $illInformation = $this->getIllInformation($user->username);
            $view->hasIllAccount = !empty($illInformation);
            $view->oldQuantity = $illInformation['balance'] ?? 0;
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
            $illInformation = $this->getIllInformation($user->username);
        }
        catch (ErrorException | \Exception $e) {
            $this->logError($e);
            $view->exception = $e;
        }

        $view->hasAccount = !empty($illInformation);

        if ($request->getPost('workrelated', false)) {
            $this->sendEmail(
                'Antrag auf dienstliches Fernleihguthaben',
                'Email/ill/work-related', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                    'quantity' => $view->chargeQuantity,
                    'department' => $request->getPost('department', ''),
                    'facility' => $request->getPost('facility', ''),
                ]
            );

            $this->flashMessage('success', 'ill_send_mail_success');
        }
        elseif(!$view->hasAccount) {
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

            $this->flashMessage('success', 'ill_send_mail_success');
        }
        elseif($request->getPost('confirmation', false) !== 'true') {
            $view->newQuantity = ($illInformation['balance'] ?? 0) + $view->chargeQuantity;
            $view->oldTotalDue = $this->getTotalDue();
            $view->newTotalDue = $view->oldTotalDue + $view->cost;
            if($view->workrelated = $request->getPost('workrelated', false)) {
                $view->department = $request->getPost('department', false);
                $view->facility = $request->getPost('facility', false);
            }
        }
        else {
            $ilsPM = $this->serviceLocator->get(\VuFind\ILS\Driver\PluginManager::class);

            if($ilsPM->get('Sera')->chargeIllFee($user->username, $view->chargeQuantity, $view->cost)) {
                try {
                    $ilsPM->get('cbsuserdpo')->chargeCredits($user->username, $view->chargeQuantity);

                    $this->flashMessage('success', 'ill_charge_success', [
                        '%%cost%%' => $this->getViewRenderer()->safeMoneyFormat($view->cost)
                    ]);
                }
                catch (\Exception $e) {
                    $this->logError($e);
                    $this->sendChargeErrorEmail($view->chargeQuantity, $view->cost);

                    $view->exception = $e;
                    $view->chargeException = true;
                }
            }
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
                'Fernleihpasswort',
                'Email/ill/forgot-password', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                ]
            );

            $this->flashMessage('success', 'ill_send_mail_success');
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
            $this->sendEmail(
                'Fernleihkonto löschen',
                'Email/ill/delete-account', [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                ]
            );

            $this->flashMessage('success', 'ill_send_mail_success');
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
     */
    protected function sendEmail(string $subject, string $template, array $data = []) : bool {
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

    protected function sendChargeErrorEmail(string $quantity, string $cost) {
        $user = $this->getUser();
        $this->sendEmail(
            'Fehler beim Buchen von Fernleihguthaben',
            'Email/ill/charge-error', [
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'username' => $user->username,
                'quantity' => $quantity,
                'cost' => $cost
            ]
        );
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

    protected function flashMessage(string $namespace, string $message, array $tokens = []) : void {
        $this->flashMessenger()->addMessage([
            'html' => true,
            'msg' => $message,
            'tokens' =>  $tokens,
        ], $namespace);
    }
}
