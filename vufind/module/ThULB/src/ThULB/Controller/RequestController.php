<?php

namespace ThULB\Controller;

use IOException;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mime\Message;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ThULB\Log\LoggerAwareTrait;
use VuFind\Controller\RecordController as OriginalRecordController;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Exception\Mail as MailException;
use VuFind\Mailer\Mailer;
use VuFind\RecordDriver\DefaultRecord;
use Whoops\Exception\ErrorException;

class RequestController extends OriginalRecordController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ChangePasswordTrait;

    protected Config $mainConfig;
    protected Config $thulbConfig;

    protected array $inventory = array();

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     * @param Config $config VuFind configuration
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ServiceLocatorInterface $sm, Config $config)
    {
        // Call standard record controller initialization:
        parent::__construct($sm, $config);

        $this->mainConfig = $config;
        $this->thulbConfig = $sm->get('VuFind\Config')->get('thulb');
        $this->setLogger($sm->get('VuFind\Logger'));

        $this->accessPermission = "access.JournalRequest";
    }

    /**
     * Action for placing an article request.
     *
     * @return ViewModel|null
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function articleAction() : ?ViewModel {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        // Block invalid requests:
        $validRequest = $this->getILS()->checkStorageRetrievalRequestIsValid(
            $this->loadRecord()->getUniqueID(), [], $this->catalogLogin()
        );
        if ((is_array($validRequest) && !$validRequest['valid']) || !$validRequest) {
            $this->flashMessenger()->addErrorMessage(
                is_array($validRequest)
                    ? $validRequest['status']
                    : 'storage_retrieval_request_error_blocked'
            );
            return null;
        }

        $series = $this->driver->getSeries(false);

        // get first configured departmentId in parent's holdings to determine email receiver and displayed details
        $seriesDriver = $this->driver->getParentDriver();
        if (!$seriesDriver) {
            $this->addFlashMessage(false, 'storage_retrieval_request_article_not_available');
            return (new ViewModel())->setTemplate('Helpers/flashMessages.phtml');
        }

        $holdings = $seriesDriver->getHoldings();
        $depId = $seriesCallnumber = null;
        foreach ($holdings['holdings'] as $holding) {
            foreach ($holding['items'] as $item) {
                if ($this->isConfiguredDepartmentId($item['departmentId'])) {
                    $depId = $item['departmentId'];
                    $seriesCallnumber = $item['callnumber'];
                    break 2;
                }
            }
        }

        if (!$depId) {
            $this->addFlashMessage(false, 'storage_retrieval_request_article_not_available');
            return (new ViewModel())->setTemplate('Helpers/flashMessages.phtml');
        }

        // collect form data
        $formData = array (
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'username' => $user['cat_id'],
            'email' => $user['email'],
            'title' => $this->driver->getTitle(),
            'series' => $series[0]['name'] . ' ' . $series[0]['number'],
            'seriesCallnumber' => $seriesCallnumber,
            'comment' => $this->params()->fromPost('comment', ''),
            'departmentId' => $depId,
        );

        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('submitArticleRequest', false)) {
            $archiveEmail = $this->getArchiveEmailForCallnumber('', $depId);
            $borrowCounter = $this->getBorrowCounterForCallnumber('', $depId);
            $locationUrl = $this->getLocationUrlForCallnumber('', $depId);

            if ($this->sendArticleRequestEmail($formData, $holdings, $archiveEmail)) {
                if ($user['email'] ?? false) {
                    $this->sendArticleConfirmationEmail($formData, $holdings, $user['email']);
                }

                $this->addFlashMessage(true, 'storage_retrieval_request_article_succeeded',
                    ['%%location%%' => $borrowCounter, '%%url%%' => $locationUrl]);
            }
            else {
                $this->addFlashMessage(false, 'storage_retrieval_request_article_failed');
            }
        }

        return new ViewModel([
            'allowed' => true,
            'formData' => $formData,
            'recordId' => $this->loadRecord()->getUniqueID(),
            'inventory' => $this->getInventoryForRequest()
        ]);
    }

    /**
     * Action for placing a journal request.
     *
     * @return ViewModel|null
     *
     * @throws ContainerExceptionInterface
     * @throws IOException
     * @throws NotFoundExceptionInterface
     */
    public function journalAction() : ?ViewModel {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        // Block invalid requests:
        $validRequest = $this->getILS()->checkStorageRetrievalRequestIsValid(
            $this->loadRecord()->getUniqueID(), [], $this->catalogLogin()
        );
        if ((is_array($validRequest) && !$validRequest['valid']) || !$validRequest) {
            $this->flashMessenger()->addErrorMessage(
                is_array($validRequest)
                    ? $validRequest['status']
                    : 'storage_retrieval_request_error_blocked'
            );
            return null;
        }

        $savePath = $this->thulbConfig->JournalRequest->request_save_path ?? false;
        if ((!file_exists($savePath) && !mkdir($savePath)) || !is_readable($savePath) || !is_writable($savePath)) {
            throw new IOException('File not writable: "' . $savePath . '"');
        }

        $params = $this->params();
        $inventory = $this->getInventoryForRequest();
        $defaultItem = count($inventory) == 1 ? array_key_first($inventory) : '';
        $formData = array (
            'firstname'  => $params->fromPost('firstname', $user['firstname']),
            'lastname'   => $params->fromPost('lastname', $user['lastname']),
            'username'   => $params->fromPost('username', $user['cat_id']),
            'title'      => $params->fromPost('title', $this->loadRecord()->getTitle()),
            'year'       => $params->fromPost('year', ''),
            'volume'     => $params->fromPost('volume', ''),
            'issue'      => $params->fromPost('issue', ''),
            'pages'      => $params->fromPost('pages', ''),
            'comment'    => $params->fromPost('comment', ''),
            'item'       => $params->fromPost('item', $defaultItem),
        );

        if ($this->getRequest()->isPost() && $this->validateFormData($formData)) {
            $fileName = $formData['username'] . '__' . date('Y_m_d__H_i_s') . '.pdf';
            $callNumber = $this->getInventoryForRequest()[$formData['item']]['callnumber'];
            $archiveEmail = $this->getArchiveEmailForCallnumber($callNumber);
            $borrowCounter = $this->getBorrowCounterForCallnumber($callNumber);
            $locationUrl = $this->getLocationUrlForCallnumber($callNumber);

            if ($this->createJournalPDF($formData, $fileName) &&
                $this->sendJournalRequestEmail($fileName, $archiveEmail)) {

                if ($user['email'] ?? false) {
                    $this->sendJournalConfirmationEmail($formData, $user['email']);
                }

                $this->addFlashMessage(true, 'storage_retrieval_request_journal_succeeded',
                    ['%%location%%' => $borrowCounter, '%%url%%' => $locationUrl]);
            }
            else {
                $this->addFlashMessage(false, 'storage_retrieval_request_journal_failed');
            }
        }

        return new ViewModel([
            'allowed' => true,
            'formData' => $formData,
            'recordId' => $this->loadRecord()->getUniqueID(),
            'inventory' => $this->getInventoryForRequest()
        ]);
    }

    public function orderedAction() {
        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $this->loadRecord();

        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('submitOrderedRequest', false)) {
            if ($this->sendOrderedRequestEmail($this->driver, $user, $this->thulbConfig->OrderedMediaRequest->email)) {
                $this->addFlashMessage(true, 'storage_retrieval_request_ordered_succeeded');
            }
            else {
                $this->addFlashMessage(false, 'storage_retrieval_request_ordered_failed');
            }
        }

        return new ViewModel([
            'allowed' => true,
            'recordId' => $this->driver->getUniqueID(),
            'title' => $this->driver->getTitle(),
            'authors' => $this->driver->getDeduplicatedAuthors(),
        ]);
    }

    /**
     * Get the items available in journal request form.
     * Performs a DAIA-Request for the current record and returns a filtered list.
     *
     * Return format:
     *     array (
     *         array (
     *             'departmentId' => ...,
     *             'callnumber' => ...,
     *             'location' => ...,
     *             'chronology' => ...
     *         ),
     *         ...
     *     )
     *
     * @return array Array of available items.
     */
    protected function getInventoryForRequest() : array {
        if(!$this->inventory) {
            $archiveIds = array_keys($this->thulbConfig->JournalRequest->ArchiveEmail->toArray());
            $holdings = $this->loadRecord()->getRealTimeHoldings();
            foreach ($holdings['holdings'] as $location => $holding) {
                foreach ($holding['items'] as $index => $item) {
                    if (!in_array($item['departmentId'], $archiveIds)) {
                        continue;
                    }

                    $key = sprintf("%s_%s_%02d", $location, $item['callnumber'], $index);
                    $this->inventory[$key] = array(
                        'departmentId' => $item['departmentId'],
                        'callnumber'   => $item['callnumber'],
                        'location'     => $location,
                        'chronology'   => !empty($item['chronology_about']) ? $item['chronology_about'] : $item['about']
                    );
                }
            }
            ksort($this->inventory);
        }

        return $this->inventory;
    }

    /**
     * Send an article request email to the library's staff.
     *
     * @param array  $formData
     * @param array  $holdings  Holdings of the parent
     * @param string $recipient Email's recipient.
     *
     * @return bool Success sending the email.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendArticleRequestEmail(array $formData, array $holdings, string $recipient) : bool {
        try {
            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->setContent(htmlspecialchars_decode(
                $this->getViewRenderer()->render('Email/request-article', [
                    'username' => $formData['username'],
                    'firstname' => $formData['firstname'],
                    'lastname' => $formData['lastname'],
                    'email' => $formData['email'],
                    'title' => $formData['title'],
                    'series' => $formData['series'],
                    'seriesCallnumber' => $formData['seriesCallnumber'],
                    'comment' => $formData['comment'],
                    'holdings' => $holdings,
                ])
            ));

            $mimeMessage = new Message();
            $mimeMessage->setParts(array($text));

            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $recipient,
                $this->mainConfig->Mail->default_from,
                $this->translate('storage_retrieval_request_article_email_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Create the pdf for the request and save it.
     *
     * @param DefaultRecord $driver Data to create pdf with.
     * @param string        $recipient
     *
     * @return bool Success of the pdf creation.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendOrderedRequestEmail(DefaultRecord $driver, UserEntityInterface $user, string $recipient) : bool {
        try {
            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->setContent(htmlspecialchars_decode(
                $this->getViewRenderer()->render('Email/request-ordered', [
                    'username' => $user->getUsername(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'title' => $driver->getTitle(),
                    'ppn' => $driver->getUniqueID(),
                ])
            ));

            $mimeMessage = new Message();
            $mimeMessage->setParts(array($text));

            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $recipient,
                $this->mainConfig->Mail->default_from,
                $this->translate('request_ordered_item_staff_mail_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Create the pdf for the journal request and save it.
     *
     * @param array  $formData Data to create pdf with.
     * @param string $fileName Name for the pdf to vbe saved as.
     *
     * @return bool Success of the pdf creation.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function createJournalPDF(array $formData, string $fileName) : bool {
        try {
            $savePath = $this->thulbConfig->JournalRequest->request_save_path ?? false;

            $pdf = $this->serviceLocator
                ->get(\ThULB\PDF\PluginManager::class)
                ->get(\ThULB\PDF\JournalRequest::class);

            $pdf->setCallNumber($this->getInventoryForRequest()[$formData['item']]['callnumber']);
            $pdf->setComment($formData['comment']);
            $pdf->setVolume($formData['volume']);
            $pdf->setIssue($formData['issue']);
            $pdf->setPages($formData['pages']);
            $pdf->setFirstName($formData['firstname']);
            $pdf->setLastName($formData['lastname']);
            $pdf->setUserName($formData['username']);
            $pdf->setWorkTitle($formData['title']);
            $pdf->setYear($formData['year']);

            $pdf->create();
            $pdf->Output('F', $savePath . $fileName);
        }
        catch (ErrorException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Send a journal request email to the library's staff.
     *
     * @param string $fileName  Name of the file to be attached to the email
     * @param string $recipient Recipient of the email.
     *
     * @return bool Success of sending the email.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendJournalRequestEmail(string $fileName, string $recipient) : bool{
        try {
            $savePath = $this->thulbConfig->JournalRequest->request_save_path ?? false;

            // first create the parts
            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';

            $fileContent = file_get_contents($savePath . $fileName, 'r');
            $attachment = new Part($fileContent);
            $attachment->type = 'application/pdf';
            $attachment->encoding = Mime::ENCODING_BASE64;
            $attachment->filename = $fileName;
            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;

            // then add them to a MIME message
            $mimeMessage = new Message();
            $mimeMessage->setParts(array($text, $attachment));

            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $recipient,
                $this->mainConfig->Mail->default_from,
                $this->translate('storage_retrieval_request_journal_email_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Send a confirmation email for the article request to the user.
     *
     * @param array  $formData
     * @param array  $holdings  Holdings of the parent
     * @param string $recipient Email's recipient.
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendArticleConfirmationEmail(array $formData, array $holdings, string $recipient) : bool {
        try {
            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->setContent(htmlspecialchars_decode(
                $this->getViewRenderer()->render('Email/request-article-confirmation', [
                    'username' => $formData['username'],
                    'firstname' => $formData['firstname'],
                    'lastname' => $formData['lastname'],
                    'title' => $formData['title'],
                    'series' => $formData['series'],
                    'seriesCallnumber' => $formData['seriesCallnumber'],
                    'comment' => $formData['comment'],
                    'holdings' => $holdings,
                    'departmentId' => $formData['departmentId'],
                    'informationEmail' => $this->getInformationEmailForCallnumber('', $formData['departmentId']),
                ])
            ));

            $mimeMessage = new Message();
            $mimeMessage->setParts(array($text));

            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $recipient,
                $this->mainConfig->Mail->default_from,
                $this->translate('storage_retrieval_request_article_confirmation_email_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Send a confirmation email for the journal request to the recipient.
     *
     * @param array  $formData
     * @param string $recipient
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendJournalConfirmationEmail(array $formData, string $recipient) : bool {
        try {
            $callNumber = $this->getInventoryForRequest()[$formData['item']]['callnumber'];

            if(strtolower($this->getDepartmentIdForCallnumber($callNumber)) == "mag6") {
                // don't send a confirmation email when ordering from mag6
                return true;
            }

            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->setContent(htmlspecialchars_decode(
                $this->getViewRenderer()->render('Email/request-journal-confirmation', [
                    'username' => $formData['username'],
                    'firstname' => $formData['firstname'],
                    'lastname' => $formData['lastname'],
                    'title' => $formData['title'],
                    'callnumber' => $callNumber,
                    'year' => $formData['year'],
                    'volume' => $formData['volume'],
                    'issue' => $formData['issue'],
                    'pages' => $formData['pages'],
                    'comment' => $formData['comment'],
                    'departmentId' => $this->getDepartmentIdForCallnumber($callNumber),
                    'informationEmail' => $this->getInformationEmailForCallnumber($callNumber),
                ])
            ));

            $mimeMessage = new Message();
            $mimeMessage->setParts(array($text));

            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
                $recipient,
                $this->mainConfig->Mail->default_from,
                $this->translate('storage_retrieval_request_journal_confirmation_email_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Check if there is a configuration for the given department id.
     *
     * @param string|null $departmentId
     *
     * @return bool
     */
    protected function isConfiguredDepartmentId (?string $departmentId): bool {
        return isset($this->thulbConfig->JournalRequest->ArchiveEmail[$departmentId])
            && isset($this->thulbConfig->JournalRequest->InformationEmail[$departmentId])
            && isset($this->thulbConfig->JournalRequest->BorrowCounter[$departmentId])
            && isset($this->thulbConfig->JournalRequest->LocationUrl[$departmentId]);
    }

    /**
     * Gets the department id for the given callnumber.
     *
     * @param $callnumber
     *
     * @return string|null
     */
    protected function getDepartmentIdForCallnumber ($callnumber) : string|null {
        foreach($this->getInventoryForRequest() as $archive) {
            if ($archive['callnumber'] == $callnumber) {
                return $archive['departmentId'];
            }
        }

        return null;
    }

    /**
     * Gets the configured archive email address for the given callnumber.
     *
     * @param string $callnumber
     * @param string|false $departmentId
     *
     * @return string|null
     */
    protected function getArchiveEmailForCallnumber(string $callnumber, string|false $departmentId = false) : string|null {
        if (!$departmentId) {
            $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
        }
        return $this->thulbConfig->JournalRequest->ArchiveEmail[$departmentId] ?: null;
    }

    /**
     * Gets the configured information email address for the given callnumber.
     *
     * @param string $callnumber
     * @param string|false $departmentId
     *
     * @return string|null
     */
    protected function getInformationEmailForCallnumber(string $callnumber, string|false $departmentId = false) : string|null {
        if (!$departmentId) {
            $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
        }
        return $this->thulbConfig->JournalRequest->InformationEmail[$departmentId] ?: null;
    }

    /**
     * Gets the configured email for the given email.
     *
     * @param string $callnumber
     * @param string|false $departmentId
     *
     * @return string|null
     */
    protected function getBorrowCounterForCallnumber(string $callnumber, string|false $departmentId = false) : string|null {
        if (!$departmentId) {
            $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
        }
        return $this->thulbConfig->JournalRequest->BorrowCounter[$departmentId] ?: null;
    }

    /**
     * Gets the configured email for the given email.
     *
     * @param string $callnumber
     * @param string|false $departmentId
     *
     * @return string|null
     */
    protected function getLocationUrlForCallnumber(string $callnumber, string|false $departmentId = false) : string|null {
        if (!$departmentId) {
            $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
        }
        return $this->thulbConfig->JournalRequest->LocationUrl[$departmentId] ?: null;
    }

    /**
     * Validate form data.
     *
     * @param array $formData
     *
     * @return bool
     */
    protected function validateFormData(array $formData) : bool {
        $error = false;
        if(!array_key_exists($formData['item'] ?? null, $this->getInventoryForRequest())) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_field_empty',
                ['%%field%%' => 'storage_retrieval_request_select_location']
            );
            $error = true;
        }
        if(empty($formData['year']) && empty($formData['comment'])) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_fields_empty',
                ['%%field1%%' => 'storage_retrieval_request_year', '%%field2%%' => 'Note']
            );
            $error = true;
        }
        return !$error;
    }

    /**
     * Adds a flash message.
     *
     * @param bool   $success       Type of flash message. TRUE for success message, FALSE for error message.
     * @param string $messageKey    Key of the message to translate.
     * @param array  $messageFields Additional fields to translate and insert into the message.
     */
    private function addFlashMessage(bool $success, string $messageKey, array $messageFields = []): void {
        foreach ($messageFields as $field => $message) {
            $messageFields[$field] = $this->translate($message);
        }

        $this->flashMessenger()->addMessage(
            array (
                'html' => true,
                'msg' => $messageKey,
                'tokens' => $messageFields
            ),
            $success ? 'success' : 'error'
        );
    }
}
