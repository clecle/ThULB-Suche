<?php

namespace EAH\PDF;

use ThULB\PDF\LetterOfAuthorization as OriginalPDF;

class LetterOfAuthorization extends OriginalPDF
{
    protected int $printBorderLeft = 15;

    protected string $authorizedDateOfBirth;

    /**
     * Create the request pdf. Data must be set beforehand.
     */
    public function create() : void {
        $this->fontpath = APPLICATION_PATH . '/themes/thulb-bs5/fonts/';
        $this->AddPage();
        $this->AddFont('Roboto', '',  'Roboto-Regular.ttf', true);
        $this->AddFont('Roboto', 'B', 'Roboto-Bold.ttf', true);
        $this->AddFont('Roboto', 'I', 'Roboto-Italic.ttf', true);
        $this->SetFont('Roboto', '',  self::DEFAULT_FONT_SIZE);
        $this->SetFontSize(11);
        $this->SetMargins($this->printBorderLeft, $this->printBorderTop, $this->printBorderRight);
        $this->SetAutoPageBreak(true, 0);

        $this->addBackgroundImage();
        $this->addContentSection();
    }

    /**
     * Add EAH background image
     */
    protected function addBackgroundImage() : void {
        $this->Image(APPLICATION_PATH . "/themes/eah-bs5/images/LoA-bg.png", 0, 0, $this->dinA4width, $this->dinA4height);
    }

    /**
     * Add the content regarding the user to the pdf
     */
    protected function addContentSection() : void {
        $spaceBetweenSections = 10;
        $colWidthBarcode = 50;
        $colWidthIssuer = $this->dinA4width / 2 - $this->printBorderLeft - 5;
        $colWidthThoska = $this->dinA4width / 2 - $this->printBorderRight - $colWidthBarcode;

        // title
        $this->SetXY($this->printBorderLeft, 25);
        $this->addText('Letter of Authorization', 'de', 0, self::FONT_BOLD, 18, self::ALIGN_CENTER);
        $this->addText('Letter of Authorization', 'en', 0, self::FONT_ITALIC, 18, self::ALIGN_CENTER);
        $this->addSpace(10);

        // issuer
        $this->useEAHColor();
        $this->addText('Authorisor');
        $this->useDefaultColor();
        $this->addText('Authorisor', 'en', 0, self::FONT_ITALIC);
        $this->addSpace($spaceBetweenSections);

        $yIssuerStart = $this->GetY();
        $this->addText($this->issuerName ?? '', 'de', $colWidthIssuer, self::FONT_BOLD);
        $this->addSpace(1, false);
        for($i = 0; $i < 3; $i++) {
            $this->addText($this->issuerAddress[$i] ?? '', 'de', $colWidthIssuer);
            $this->addSpace(1, false);
        }
        $yIssuerEnd = $this->GetY();

        $this->SetXY($this->dinA4width / 2, $yIssuerStart);
        $this->addText('Thoska No', 'de', $colWidthThoska);
        $this->addText('Thoska-No', 'en', $colWidthThoska, self::FONT_ITALIC);

        $this->SetXY($this->GetX() + $colWidthThoska, $yIssuerStart);
        $this->addText($this->issuerUserNumber ?? '', 'de', $colWidthBarcode);
        $this->addSpace(2, false);
        $this->Image($this->barcode);

        // terms
        $yTermsStart = $yIssuerEnd + $spaceBetweenSections;
        $this->SetLeftMargin($this->printBorderLeft + 7);
        $this->SetXY($this->printBorderLeft + 7, $yTermsStart);
        $this->addText('letter_of_authorization_pdf_paragraph_1', 'de', $this->contentMaxWidth - 7, self::FONT_DEFAULT, 0, self::ALIGN_BLOCK, 2.5);
        $this->addSpace(8, false);
        $this->addText('letter_of_authorization_pdf_paragraph_1', 'en', $this->contentMaxWidth - 7, self::FONT_ITALIC, 0, self::ALIGN_BLOCK, 2.5);
        $this->SetLeftMargin($this->printBorderLeft);
        $yTermsEnd = $this->GetY();

        $this->useEAHColor();
        $this->SetLineWidth(2);
        $this->Line($this->printBorderLeft + 1, $yTermsStart + 1, $this->printBorderLeft + 1, $yTermsEnd + 5);
        $this->addSpace($spaceBetweenSections + $this->FontSize + 2.5);
        $this->SetLineWidth(0.2);

        // authorized person
        $this->useEAHColor();
        $this->addText('Authorized person');
        $this->useDefaultColor();
        $this->addText('Authorized person', 'en', 0, self::FONT_ITALIC);
        $this->addSpace();

        $yAuthorizedStart = $this->GetY();
        $this->addText($this->authorizedName, 'de', $colWidthIssuer);

        $this->SetXY($this->dinA4width / 2, $yAuthorizedStart);
        $this->addText('Date of birth', 'de', $colWidthThoska);
        $this->addText('Date of birth', 'en', $colWidthThoska, self::FONT_ITALIC);
        $yAuthorizedEnd = $this->GetY();

        $this->SetXY($this->GetX() + $colWidthThoska, $yAuthorizedStart);
        $this->addText($this->authorizedDateOfBirth ?? 'authorizedDateOfBirth', 'de', $colWidthBarcode);

        // granted until
        $this->SetXY($this->printBorderLeft, $yAuthorizedEnd + $spaceBetweenSections);
        $this->useEAHColor();
        $this->addText('Granted until');
        $this->useDefaultColor();
        $this->addText('Granted until', 'en', 0, self::FONT_ITALIC);
        $this->addSpace(3);
        $this->addText($this->grantedUntil);

        // place, date
        $this->addSpace($spaceBetweenSections + 10);
        $this->Line($this->printBorderLeft, $this->GetY(), $this->printBorderLeft + $colWidthIssuer, $this->GetY());
        $ySignature = $this->GetY();
        $this->addText('Place, Date');
        $this->SetXY($this->GetX() + $this->getTranslatedStringWidth('Place, Date') + 2, $ySignature);
        $this->addText('Place, Date', 'en', 0, self::FONT_ITALIC);

        // signature
        $this->SetXY($this->dinA4width / 2, $ySignature);
        $this->Line($this->dinA4width / 2, $this->GetY(), $this->dinA4width / 2 + $colWidthThoska + $colWidthBarcode, $this->GetY());
        $ySignature = $this->GetY();
        $this->addText('Signature');
        $this->SetXY($this->GetX() + $this->getTranslatedStringWidth('Signature') + 2, $ySignature);
        $this->addText('Signature', 'en', 0, self::FONT_ITALIC);
    }

    protected function useEAHColor() : void {
        $this->SetTextColor(0, 170, 160);
        $this->SetDrawColor(204, 238, 236);
    }

    protected function useDefaultColor() : void {
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
    }

    public function setAuthorizedDateOfBirth(string $dateOfBirth) : void {
        $this->authorizedDateOfBirth = $dateOfBirth;
    }
}