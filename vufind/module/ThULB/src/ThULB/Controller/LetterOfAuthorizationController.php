<?php

namespace ThULB\Controller;

use IOException;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mime\Message;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ThULB\Log\LoggerAwareTrait;
use VuFind\Controller\AbstractBase;
use VuFind\Exception\Mail as MailException;
use VuFind\Http\PhpEnvironment\Request;
use VuFind\Mailer\Mailer;
use Whoops\Exception\ErrorException;

class LetterOfAuthorizationController extends AbstractBase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected Config $mainConfig;
    protected Config $letterOfAuthorization;
    protected string $letterOfAuthorizationSavePath;

    protected array $requiredFormFields = ['firstname', 'lastname', 'grantUntil', 'check1', 'check2'];

    public function __construct(ServiceLocatorInterface $serviceLocator) {
        parent::__construct($serviceLocator);

        $configLoader = $serviceLocator->get(\VuFind\Config\PluginManager::class);
        $this->mainConfig = $configLoader->get('config');
        $this->letterOfAuthorization =
            $configLoader->get('thulb')->LetterOfAuthorization ?? false;
        $this->letterOfAuthorizationSavePath =
            $this->letterOfAuthorization->pdf_save_path ?? false;
    }

    /**
     * Execute the request.
     * Logout logged-in users if the ILS Driver switched to an offline mode and redirect to login screen.
     *
     * @param  MvcEvent $e
     *
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeName = 'myresearch-userlogin';
        if($this->getILS()->getOfflineMode()
            && strtolower($e->getRouteMatch()->getMatchedRouteName()) !== $routeName
            && $this->getAuthManager()->getUserObject()) {

            $e->getRouteMatch()->setParam('action', 'logout');
            parent::onDispatch($e);

            return $this->redirect()->toRoute($routeName);
        }

        return parent::onDispatch($e);
    }

    /**
     * Process the creation of a letter of authority for the user.
     *
     * @return ViewModel
     *
     * @throws ContainerExceptionInterface
     * @throws IOException
     * @throws NotFoundExceptionInterface
     */
    public function createAction() : ViewModel {
        if(!$this->letterOfAuthorization->enabled ?? false) {
            return (new ViewModel(['message' => 'Page not found.']))->setTemplate('error/404');
        }

        if (!$this->getAuthManager()->getUserObject()) {
            return $this->forceLogin();
        }

        $savePath = $this->letterOfAuthorizationSavePath;
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
            foreach ($this->requiredFormFields as $field) {
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
            if(!$errors && $this->createPDF($request, $fileName, $ilsUser) &&
                $this->sendEmail($user['firstname'] . ' ' . $user['lastname'], $user['email'], $fileName)) {
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
     * @param Request  $request  Request with the form data
     * @param string   $fileName Name to save the pdf with.
     * @param string[] $ilsUser  Userdata from ILS
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function createPDF(Request $request, string $fileName, array $ilsUser) : bool {
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

            $pdf = $this->serviceLocator
                ->get(\ThULB\PDF\PluginManager::class)
                ->get(\ThULB\PDF\LetterOfAuthorization::class);
            $pdf->setBarcode($barcodeFile);
            $pdf->setAuthorizedName($request->getPost('firstname') . ' ' . $request->getPost('lastname'));
            $pdf->setGrantedUntil(date('d.m.Y', strtotime($request->getPost('grantUntil'))));
            $pdf->setIssuerEMail($user['email']);
            $pdf->setIssuerName($user['firstname'] . ' ' . $user['lastname']);
            $pdf->setIssuerUserNumber($user['username']);
            $pdf->setIssuerAddress(explode(',', $ilsUser['address1'] ?? ''));

            $pdf->create();
            $pdf->Output('F', $this->letterOfAuthorizationSavePath . $fileName);
        }
        catch (ErrorException $e) {
            $this->logException($e);

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
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendEmail(string $name, string $email, string $pdfFilename) : bool {
        try {
            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->setContent(htmlspecialchars_decode(
                $this->getViewRenderer()->render('Email/letter-of-authorization', [
                    'name' => $name,
                ])
            ));


            $fileContent = file_get_contents($this->letterOfAuthorizationSavePath . $pdfFilename, 'r');
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
            $this->logException($e);

            return false;
        }

        return true;
    }
}
