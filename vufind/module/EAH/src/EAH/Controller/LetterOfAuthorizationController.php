<?php

namespace EAH\Controller;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ThULB\Controller\LetterOfAuthorizationController as OriginalLetterOfAuthorizationController;
use VuFind\Http\PhpEnvironment\Request;
use Whoops\Exception\ErrorException;

class LetterOfAuthorizationController extends OriginalLetterOfAuthorizationController {
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
            $pdf->setAuthorizedDateOfBirth(date('d.m.Y', strtotime($request->getPost('dateOfBirth'))));
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
}
