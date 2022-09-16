<?php

namespace ThULB\PDF;

use tFPDF;
use VuFind\View\Helper\Root\Translate;

class LetterOfAuthorisation extends tFPDF
{
    // All Dimensions in 'mm'
    protected $dinA4width = 210;
    protected $dinA4height = 297;
    protected $widthThULBSection = 40;
    protected $widthContentSection = 120;
    protected $printBorderBottom = 15;
    protected $printBorderLeft = 25;
    protected $printBorderRight = 10;
    protected $printBorderTop = 15;

    protected $authorizedName;

    protected $barcode;
    protected $grantedUntil;
    protected $issuerEMail;
    protected $issuerName;
    protected $issuerUserNumber;
    protected $issuerAddress;

    protected const DEFAULT_FONT_SIZE = 10;
    protected const TRANSLATION_PREFIX = 'Email::';

    protected $translator;

    /**
     * Constructor.
     *
     * @param Translate $translator Translator to use.
     * @param string $orientation Page orientation.
     * @param string $unit Unit to measure pages.
     * @param string $size Size of the pages.
     */
    public function __construct(Translate $translator, string $orientation = 'P', string $unit = 'mm', string $size = 'A4') {
        parent::__construct($orientation, $unit, $size);

        $this->translator = $translator;
        $this->translator->getTranslator()->addTranslationFile('ExtendedIni', 'Email', 'Email', 'de');
        $this->translator->getTranslator()->addTranslationFile('ExtendedIni', 'Email', 'Email', 'en');
        $this->contentMaxWidth = $this->dinA4width - $this->printBorderLeft - $this->printBorderRight;
    }

    /**
     * Create the request pdf. Data must be set beforehand.
     */
    public function create() : void {
        $this->fontpath = APPLICATION_PATH . '/themes/thulb/fonts/';
        $this->AddPage();
        $this->AddFont('Roboto', '',  'Roboto-Regular.ttf', true);
        $this->AddFont('Roboto', 'B', 'Roboto-Bold.ttf', true);
        $this->AddFont('Roboto', 'I', 'Roboto-Italic.ttf', true);
        $this->SetFont('Roboto', '',  self::DEFAULT_FONT_SIZE);

        $this->SetMargins($this->printBorderLeft, $this->printBorderTop, $this->printBorderRight);
        $this->SetAutoPageBreak(true, 0);

        $this->addThULBSection();
        $this->addContentSection();
    }

    /**
     * Add the image and text of the thulb on the right side
     */
    protected function addThULBSection() : void
    {
        $this->SetFontSize(8);
        $this->SetTextColor(0, 47, 93);
        $x = $this->dinA4width - $this->widthThULBSection - $this->printBorderRight;
        $this->SetXY($x - 6, $this->printBorderTop);
        $this->Image(APPLICATION_PATH . "/themes/thulb/images/thulblogo_blue.png", null, null, 35);

        $this->SetXY($x, $this->GetY() + 3);
        $this->addText('thulb_full', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('Benutzung', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('Zentrale Ausleihe', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('Bibliotheksplatz 2', 'de', $this->widthThULBSection);
        $this->addText('00743 Jena', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('Tel.: +49 (0)3641 9-404 110', 'de', $this->widthThULBSection);
        $this->addText('Fax: +49 (0)3641 9-404 132', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('ausleihe_thulb@uni-jena.de', 'de', $this->widthThULBSection);
        $this->addText('www.thulb.uni-jena.de', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('Eine Einrichtung der:', 'de', $this->widthThULBSection, [], 6);
        $this->addText('FRIEDRICH-SCHILLER-', 'de', $this->widthThULBSection, [], 9, 'B');
        $this->addText('UNIVERSITÄT', 'de', $this->widthThULBSection, [], 14, 'B');
        $this->SetX($this->GetX() - 1);
        $this->addText('JENA', 'de', $this->widthThULBSection, [], 14, 'B');

        $this->SetFontSize(self::DEFAULT_FONT_SIZE);

        $this->SetDrawColor(255,255,255);
        $this->SetFillColor(0, 47, 93);
        $this->Rect($this->printBorderLeft + 1, $this->dinA4height - $this->printBorderBottom - 15, 15, 2, 'F');
        $this->SetXY($this->printBorderLeft, $this->dinA4height - $this->printBorderBottom - 10);
        $this->addText('Letter of Authorisation', 'de', $this->widthContentSection);

        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(255,255,255);
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * Add the content regarding the user to the pdf
     */
    protected function addContentSection() : void {
        $widthHalfContentSection = $this->widthContentSection / 2;

        $this->SetY(40, true);
        $this->SetTextColor(30, 96, 190);
        $this->addText('Letter of Authorisation', 'de', $this->widthContentSection, [], 15, 'B');
        $this->SetTextColor(0, 0, 0);
        $this->addSpace(10);

        $y = $this->GetY();
        $this->addText('Registered_user_and_issuer', 'de', $widthHalfContentSection - 5);
        $this->addText('Registered_user_and_issuer', 'en', $widthHalfContentSection - 5, [], 0, 'I');
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection + 5, $y);
        $this->addText($this->issuerName ?? '', 'de', $widthHalfContentSection - 5);
        for($i = 0; $i < 3; $i++) {
            $this->addText($this->issuerAddress[$i] ?? '', 'de', $widthHalfContentSection - 5);
        }
        $this->SetX($this->printBorderLeft);
        $this->addSpace(10);

        $y = $this->GetY();
        $this->addText('username', 'de', $widthHalfContentSection - 5);
        $this->addText('username', 'en', $widthHalfContentSection - 5, [], 0, 'I');
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection + 5, $y);
        $this->addText($this->issuerUserNumber, 'de', $widthHalfContentSection - 5);
        $this->addSpace(3, false);
        $this->Image($this->barcode);
        $this->SetX($this->printBorderLeft);
        $this->addSpace(10);

        $this->addText('letter_of_authorisation_pdf_paragraph_1', 'de', $this->widthContentSection);
        $this->addSpace(2);
        $this->addText('letter_of_authorisation_pdf_paragraph_2', 'de', $this->widthContentSection);
        $this->addSpace(5);
        $this->addText('letter_of_authorisation_pdf_paragraph_1', 'en', $this->widthContentSection, [], 0, 'I');
        $this->addSpace(2);
        $this->addText('letter_of_authorisation_pdf_paragraph_2', 'en', $this->widthContentSection, [], 0, 'I');
        $this->addSpace(5);

        $y = $this->GetY();
        $this->addText('Authorized person', 'de', $this->widthContentSection);
        $this->addText('Authorized person', 'en', $this->widthContentSection, [], 0, 'I');
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection, $y);
        $this->addText($this->authorizedName, 'de', $widthHalfContentSection);
        $this->SetX($this->printBorderLeft);
        $this->addSpace(10);

        $y = $this->GetY();
        $this->addText('Granted until', 'de', $this->widthContentSection);
        $this->addText('Granted until', 'en', $this->widthContentSection, [], 0, 'I');
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection, $y);
        $this->addText($this->grantedUntil, 'de', $widthHalfContentSection);
        $this->SetX($this->printBorderLeft);
        $this->addSpace(20);

        $this->addText('Date', 'de', $this->widthContentSection);
        $y = $this->GetY();
        $this->addText('Date', 'en', $this->widthContentSection, [], 0, 'I');
        $this->Line($this->printBorderLeft + 25, $y, $this->printBorderLeft + $this->widthContentSection, $y);
        $this->addSpace(10);

        $this->addText('Signature', 'de', $this->widthContentSection);
        $y = $this->GetY();
        $this->addText('Signature', 'en', $this->widthContentSection, [], 0, 'I');
        $this->Line($this->printBorderLeft + 25, $y, $this->printBorderLeft + $this->widthContentSection, $y);
    }

    /**
     * Adds a text to the pdf. X, Y coordinates have to be set beforehand.
     *
     * @param string $text          Text to be added.
     * @param int $contentWidth     Max width of the added text block
     * @param string[] $token       Tokens to insert while translating the text
     * @param int $textFontSize     Font size of the added text
     * @param string $textFontStyle Font style of the added text
     */
    protected function addText(string $text, string $language, int $contentWidth, array $token = [], int $textFontSize = 0, string $textFontStyle = '') : void {
        if($textFontSize <= 0) {
            $textFontSize = $this->FontSizePt;
        }

        $originalLanguage = $this->translator->getTranslator()->getLocale();
        $this->translator->getTranslator()->setLocale($language);
        $text = $this->translator->translateWithPrefix(self::TRANSLATION_PREFIX, $text, $token);
        $this->translator->getTranslator()->setLocale($originalLanguage);

        // Remove Zero Width Non-Joiner from translations with empty text
        if($text == "\u{200C}") {
            $text = null;
        }

        $x = $this->GetX();
        $y = $this->GetY() + 1;
        $this->SetXY($x, $y);

        // save font size to restore it after adding the text
        $tmpFontSize = $this->FontSizePt;
        $tmpFontStyle = $this->FontStyle;

        $this->SetFont($this->FontFamily, $textFontStyle, $textFontSize);
        $this->MultiCell($contentWidth, $this->FontSize, trim($text), 0, 'L');
        $this->SetXY($x, $this->GetY());

        // restore font size
        $this->SetFont($this->FontFamily, $tmpFontStyle, $tmpFontSize);
    }

    /**
     * Increases the Y-coordinate to add spacing to the current position.
     *
     * @param int $space   Space to be added
     * @param bool $resetX Reset X-coordinate
     */
    protected function addSpace(int $space = 5, bool $resetX = true) : void {
        $this->SetY($this->GetY() + $space, $resetX);
    }

    /**
     * @param string $authorizedName
     * @return LetterOfAuthorisation
     */
    public function setAuthorizedName(string $authorizedName): LetterOfAuthorisation {
        $this->authorizedName = $authorizedName;
        return $this;
    }

    /**
     * @param string $grantedUntil
     * @return LetterOfAuthorisation
     */
    public function setGrantedUntil(string $grantedUntil): LetterOfAuthorisation {
        $this->grantedUntil = $grantedUntil;
        return $this;
    }

    /**
     * @param string $issuerEMail
     * @return LetterOfAuthorisation
     */
    public function setIssuerEMail(string $issuerEMail): LetterOfAuthorisation {
        $this->issuerEMail = $issuerEMail;
        return $this;
    }

    /**
     * @param string $issuerName
     * @return LetterOfAuthorisation
     */
    public function setIssuerName(string $issuerName): LetterOfAuthorisation {
        $this->issuerName = $issuerName;
        return $this;
    }

    /**
     * @param string $issuerUserNumber
     * @return LetterOfAuthorisation
     */
    public function setIssuerUserNumber(string $issuerUserNumber): LetterOfAuthorisation {
        $this->issuerUserNumber = $issuerUserNumber;
        return $this;
    }

    /**
     * @param string[] $issuerAddress
     * @return LetterOfAuthorisation
     */
    public function setIssuerAddress(array $issuerAddress): LetterOfAuthorisation {
        $this->issuerAddress = $issuerAddress;
        return $this;
    }

    /**
     * @param mixed $barcode
     * @return LetterOfAuthorisation
     */
    public function setBarcode($barcode): LetterOfAuthorisation {
        $this->barcode = $barcode;
        return $this;
    }
}