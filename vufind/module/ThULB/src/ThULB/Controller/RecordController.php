<?php

namespace ThULB\Controller;

use Laminas\Log\LoggerAwareInterface;
use Laminas\Mime\Message;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use ThULB\Log\LoggerAwareTrait;
use VuFind\Controller\RecordController as OriginalRecordController;
use VuFind\Exception\Mail as MailException;
use VuFind\Exception\PasswordSecurity;
use VuFind\Mailer\Mailer;

class RecordController extends OriginalRecordController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ChangePasswordTrait;

    public function onDispatch(MvcEvent $event)
    {
        // ignore onDispatch from Trait to force only when certain actions are called
        return parent::onDispatch($event);
    }

    /**
     * Action for dealing with storage retrieval requests.
     *
     * @return mixed
     *
     * @throws PasswordSecurity
     */
    public function storageRetrievalRequestAction() {
        if($this->isPasswordChangeNeeded()) {
            return $this->forceNewPassword();
        }

        return parent::storageRetrievalRequestAction();
    }

    /**
     * Action for dealing with holds.
     *
     * @return mixed
     *
     * @throws PasswordSecurity
     */
    public function holdAction() {
        if($this->isPasswordChangeNeeded()) {
            return $this->forceNewPassword();
        }

        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        $view = parent::holdAction();

        if($view instanceof ViewModel) {
            $view->setVariable('duedate', $this->getRequest()->getQuery('duedate', null));
            $view->setVariable('requests_placed', $this->getRequest()->getQuery('requests_placed', null));

            $gatheredDetails = $view->getVariable('gatheredDetails');
            $holdings = $this->getILS()->getHolding($gatheredDetails['id'], $patron);
            foreach($holdings['holdings'] ?? [] as $item) {
                if($item['doc_id'] == $gatheredDetails['doc_id'] && $item['item_id'] == $gatheredDetails['item_id']) {
                    $view->setVariable('item', $item);
                    break;
                }
            }
        }

        return $view;
    }

    public function orderReserveAction() {
        // Force login if necessary:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }

        return new ViewModel([
            'driver' => $this->loadRecord()
        ]);
    }

    public function reportBrokenLinkAction() {
        $configManager = $this->serviceLocator->get(\VuFind\Config\PluginManager::class);
        $thulbConfig = $configManager->get('thulb');
        $mainConfig = $configManager->get('config');

        $request = $this->getRequest();
        $firstname = $request->getPost('firstname', $this->getUser()->firstname ?? '');
        $lastname = $request->getPost('lastname', $this->getUser()->lastname ?? '');
        $email = $request->getPost('email', $this->getUser()->email ?? '');
        $feedback = $request->getPost('feedback', false) == 'true';

        if($feedback && (empty($firstname) || empty($lastname) || empty($email))) {
            $this->flashMessenger()->addMessage('Please enter your contact information if you want feedback.', 'error');
        }
        elseif (($thulbConfig->BrokenLink->email ?? false) && $request->isPost() && !$request->getPost('mylang')) {
            try {
                $text = new Part();
                $text->type = Mime::TYPE_TEXT;
                $text->charset = 'utf-8';
                $text->setContent(htmlspecialchars_decode(
                    $this->getViewRenderer()->render('Email/broken-link', [
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'feedback' => $feedback,
                        'driver' => $this->loadRecord(),
                        'siteURL' => $mainConfig->Site->url
                    ])
                ));

                $mimeMessage = new Message();
                $mimeMessage->setParts(array($text));

                $mailer = $this->serviceLocator->get(Mailer::class);
                $mailer->setMaxRecipients(0);
                $mailer->send(
                    $thulbConfig->BrokenLink->email,
                    $mainConfig->Mail->default_from,
                    $this->translate('Email::broken_link_email_subject'),
                    $mimeMessage
                );

                $this->flashMessenger()->addMessage('Issue was reported.', 'success');
                return $this->createViewModel()->setTemplate('Helpers/flashMessages.phtml');
            }
            catch (MailException $e) {
                $this->logException($e);

                $this->flashMessenger()->addMessage('Issue could not be reported. Try later again.', 'error');
            }
        }

        return $this->createViewModel([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
        ])->setTemplate('record/report-broken-link.phtml');
    }
}