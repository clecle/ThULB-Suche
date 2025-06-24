<?php

namespace EAH\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ThULB\Controller\ILLController as OriginalController;
use VuFind\Exception\Mail as MailException;
use VuFind\Mailer\Mailer;

/**
 * @method FlashMessenger flashMessenger()
 */
class ILLController extends OriginalController
{
    public function orderItemAction() {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        return new ViewModel();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function orderItemConfirmationAction() : ViewModel | Response {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $view = new ViewModel();
        $view->setTemplate('ill/orderitem-confirmation.phtml');

        $request = $this->getRequest();
        if(!$request->isPost() || !$this->doCsrfValidation()) {
            return $this->redirect()->toUrl('/ILL/orderItem');
        }

        $this->addLogContent('Username: ' . $user->username);

        $orderData = array_merge($request->getPost()->toArray(), [
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
        ]);
        if($request->getPost('confirmation', false) !== 'true') {
            $this->addLogContent('Display order information', $orderData);
            $view->setVariables($orderData);
        }
        else {
            $this->addLogContent('Request type: direct order', $orderData);
            if ($this->sendEmail('Direktbestellung', 'Email/ill/order-item', $orderData)) {
                if ($user['email'] ?? false) {
                    $this->sendOrderItemConfirmationEmail($user['email'], 'Email::ill_order_item', 'Email/ill/order-item-confirmation', $orderData);
                }
                $this->flashMessage('success', 'ill_order_success');
            }
            else {
                $this->addLogContent('Abort ordering. Request user to try again later.', $orderData);
                $this->flashMessage('error', 'An error occurred during execution; please try again later.');
            }

            $view->clearVariables();
            $view->setTemplate('Helpers/flashMessages.phtml');
        }

        $this->writeIllLog();

        return $view;
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
    protected function sendOrderItemConfirmationEmail(string $receiver, string $subject, string $template, array $data = []) : bool {
        $subject = $this->translate($subject) . ' ' . ($data['username'] ?? '');
        $data = array_merge(['subject' => $subject], $data);

        $success = false;
        try {
            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $receiver,
                $this->mainConfig->Mail->default_from,
                $subject,
                $this->getViewRenderer()->render($template, $data)
            );

            $success = true;
        }
        catch (MailException $e) {
            $this->logException($e);
        }

        $this->addLogContent('Send confirmation mail ... ' . ($success ? 'OK' : 'Failed') . '.', $data);

        return $success;
    }
}
