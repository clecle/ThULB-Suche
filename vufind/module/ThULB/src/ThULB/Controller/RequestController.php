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
use ThULB\Log\LoggerAwareTrait;
use ThULB\PDF\JournalRequest;
use VuFind\Controller\RecordController as OriginalRecordController;
use VuFind\Exception\Mail as MailException;
use VuFind\Mailer\Mailer;
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
     * @param ServiceLocatorInterface $sm     Service manager
     * @param Config                  $config VuFind configuration
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
     * Action for placing a journal request.
     *
     * @return ViewModel|null
     *
     * @throws IOException
     */
    public function journalAction() : ?ViewModel {

        // Force login if necessary:
        if (!$this->getUser()) {
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

        $formData = $this->getFormData();

        if ($this->getRequest()->isPost() && $this->validateFormData($formData)) {
            $fileName = $formData['username'] . '__' . date('Y_m_d__H_i_s') . '.pdf';
            $callNumber = $this->getInventoryForRequest()[$formData['item']]['callnumber'];
            $archiveEmail = $this->getArchiveEmailForCallnumber($callNumber);
            $borrowCounter = $this->getBorrowCounterForCallnumber($callNumber);
            $locationUrl = $this->getLocationUrlForCallnumber($callNumber);

            if ($this->createPDF($formData, $fileName) &&
                    $this->sendRequestEmail($fileName, $archiveEmail)) {

                if($this->getUser()['email'] ?? false) {
                    $this->sendConfirmationEmail($formData, $this->getUser()['email']);
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

    /**
     * Get data array of values from the request or default values.
     *
     * @return array
     */
    protected function getFormData() : array {
        $params = $this->params();
        $user = $this->getUser();
        $inventory = $this->getInventoryForRequest();
        $defaultItem = count($inventory) == 1 ? array_key_first($inventory) : '';

        return array (
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
     * Create the pdf for the request and save it.
     *
     * @param array $formData Data to create pdf with.
     * @param string $fileName Name for the pdf to vbe saved as.
     *
     * @return bool Success of the pdf creation.
     */
    protected function createPDF(array $formData, string $fileName) : bool {
        try {
            $savePath = $this->thulbConfig->JournalRequest->request_save_path ?? false;

            $pdf = new JournalRequest($this->getViewRenderer()->plugin('translate'));

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
     * Send the request email.
     *
     * @param string $fileName Name of the file to be attached to the email
     * @param string $recipient Recipient of the email.
     *
     * @return bool Success of sending the email.
     */
    protected function sendRequestEmail(string $fileName, string $recipient) : bool{
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
                $this->translate('storage_retrieval_request_email_subject'),
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
     * Send an confirmation email for the request to the recipient.
     *
     * @param array $formData
     * @param string $recipient
     *
     * @return bool
     */
    protected function sendConfirmationEmail(array $formData, string $recipient) : bool {
        try {
            $callNumber = $this->getInventoryForRequest()[$formData['item']]['callnumber'];

            if(strtolower($this->getDepartmentIdForCallnumber($callNumber)) == "mag6") {
                // don't send a mail to mag6
                return true;
            }

            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->setContent(htmlspecialchars_decode(
                $this->getViewRenderer()->render('Email/request-confirmation', [
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
                $this->translate('storage_retrieval_request_confirmation_email_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    protected function getDepartmentIdForCallnumber ($callnumber) : ?string {
        foreach($this->getInventoryForRequest() as $archive) {
            if ($archive['callnumber'] == $callnumber) {
                return $archive['departmentId'];
            }
        }

        return false;
    }

    /**
     * Gets the configured archive email address for the given callnumber.
     *
     * @param string $callnumber
     *
     * @return string|null
     */
    protected function getArchiveEmailForCallnumber(string $callnumber) : ?string {
        if(APPLICATION_ENV == 'development' || APPLICATION_ENV == 'testing') {
            return $this->thulbConfig->JournalRequest->test_email;
        }

        $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
        return $this->thulbConfig->JournalRequest->ArchiveEmail[$departmentId] ?: null;
    }

    /**
     * Gets the configured information email address for the given callnumber.
     *
     * @param string $callnumber
     *
     * @return string|null
     */
    protected function getInformationEmailForCallnumber(string $callnumber) : ?string {
        $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
        return $this->thulbConfig->JournalRequest->InformationEmail[$departmentId] ?: null;
    }

    /**
     * Gets the configured email for the given email.
     *
     * @param string $callnumber
     *
     * @return string|null
     */
    protected function getBorrowCounterForCallnumber(string $callnumber) : ?string {
        $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
        return $this->thulbConfig->JournalRequest->BorrowCounter[$departmentId] ?: null;
    }

    /**
     * Gets the configured email for the given email.
     *
     * @param string $callnumber
     *
     * @return string|null
     */
    protected function getLocationUrlForCallnumber(string $callnumber) : ?string {
        $departmentId = $this->getDepartmentIdForCallnumber($callnumber);
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
    private function addFlashMessage(bool $success, string $messageKey, array $messageFields = []) {
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
