<?php

namespace ThULB\PDF;

use tFPDF;
use VuFind\View\Helper\Root\Translate;

class LetterOfAuthorization extends tFPDF
{
    public const ALIGN_BLOCK = 'B';
    public const ALIGN_CENTER = 'C';
    public const ALIGN_LEFT = 'L';
    public const ALIGN_RIGHT = 'R';
    public const FONT_BOLD = 'B';
    public const FONT_DEFAULT = '';
    public const FONT_ITALIC = 'I';
    public const STYLE_FILL = 'F';

    // All Dimensions in 'mm'
    protected int $dinA4width = 210;
    protected int $dinA4height = 297;
    protected int $widthThULBSection = 40;
    protected int $widthContentSection = 120;
    protected int $printBorderBottom = 15;
    protected int $printBorderLeft = 25;
    protected int $printBorderRight = 10;
    protected int $printBorderTop = 15;

    protected string $authorizedName;

    protected $barcode;
    protected string $grantedUntil;
    protected string $issuerEMail;
    protected string $issuerName;
    protected string $issuerUserNumber;
    protected array $issuerAddress;

    protected const DEFAULT_FONT_SIZE = 10;
    protected const TRANSLATION_PREFIX = 'Email::';

    protected Translate $translator;

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
        $this->fontpath = APPLICATION_PATH . '/themes/thulb-bs5/fonts/';
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
        $this->Image(APPLICATION_PATH . "/themes/thulb-bs5/images/thulblogo_blue.png", null, null, 35);

        $this->SetXY($x, $this->GetY() + 3);
        $this->addText('thulb_full', 'de', $this->widthThULBSection, self::FONT_BOLD);
        $this->addSpace(3, false);
        $this->addText('Benutzung', 'de', $this->widthThULBSection, self::FONT_BOLD);
        $this->addSpace(3, false);
        $this->addText('Zentrale Ausleihe', 'de', $this->widthThULBSection, self::FONT_BOLD);
        $this->addSpace(3, false);
        $this->addText('Bibliotheksplatz 2', 'de', $this->widthThULBSection);
        $this->addText('07743 Jena', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('Tel.: +49 (0)3641 9-404 110', 'de', $this->widthThULBSection);
        $this->addSpace(3, false);
        $this->addText('ausleihe_thulb@uni-jena.de', 'de', $this->widthThULBSection);
        $this->addText('www.thulb.uni-jena.de', 'de', $this->widthThULBSection, self::FONT_BOLD);
        $this->addSpace(3, false);
        $this->addText('Eine Einrichtung der:', 'de', $this->widthThULBSection, self::FONT_DEFAULT, 6);
        $this->addText('FRIEDRICH-SCHILLER-', 'de', $this->widthThULBSection, self::FONT_BOLD, 9);
        $this->addText('UNIVERSITÄT', 'de', $this->widthThULBSection, self::FONT_BOLD, 14);
        $this->SetX($this->GetX() - 1);
        $this->addText('JENA', 'de', $this->widthThULBSection, self::FONT_BOLD, 14);

        $this->SetFontSize(self::DEFAULT_FONT_SIZE);

        $this->SetDrawColor(255,255,255);
        $this->SetFillColor(0, 47, 93);
        $this->Rect($this->printBorderLeft + 1, $this->dinA4height - $this->printBorderBottom - 15, 15, 2, self::STYLE_FILL);
        $this->SetXY($this->printBorderLeft, $this->dinA4height - $this->printBorderBottom - 10);
        $this->addText('Letter of Authorization', 'de', $this->widthContentSection);

        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(255,255,255);
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * Add the content regarding the user to the pdf
     */
    protected function addContentSection() : void {
        $widthHalfContentSection = $this->widthContentSection / 2;
        $barcodeWidthPx = getimagesize($this->barcode)[0] ?: 0;
        $barcodeWidthMm = $barcodeWidthPx * 0.264;

        $this->SetY(31, true);
        $this->SetTextColor(33, 84, 163);
        $this->addText('Letter of Authorization', 'de', $this->widthContentSection, self::FONT_BOLD, 15);
        $this->SetTextColor(0, 0, 0);
        $this->addSpace(10);

        $y = $this->GetY();
        $this->addText('Registered_user_and_issuer', 'de', $widthHalfContentSection - 5);
        $this->addText('Registered_user_and_issuer', 'en', $widthHalfContentSection - 5, self::FONT_ITALIC);
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection, $y);
        $this->addText($this->issuerName ?? '', 'de', $widthHalfContentSection - 5);
        for($i = 0; $i < 3; $i++) {
            $this->addText($this->issuerAddress[$i] ?? '', 'de', $widthHalfContentSection - 5);
        }
        $this->SetX($this->printBorderLeft);
        $this->addSpace(10);

        $y = $this->GetY();
        $this->addText('username', 'de', $widthHalfContentSection - 5);
        $this->addText('username', 'en', $widthHalfContentSection - 5, self::FONT_ITALIC);
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection, $y);
        $this->addText($this->issuerUserNumber, 'de', $widthHalfContentSection - 5);
        $this->SetXY($this->printBorderLeft + $this->widthContentSection - $barcodeWidthMm, $this->GetY() + 3);
        $this->Image($this->barcode);
        $this->SetX($this->printBorderLeft);
        $this->addSpace(10);

        $this->addText('letter_of_authorization_pdf_paragraph_1', 'de', $this->widthContentSection, self::FONT_DEFAULT, 0, self::ALIGN_BLOCK);
        $this->addSpace(5);
        $this->addText('letter_of_authorization_pdf_paragraph_2', 'de', $this->widthContentSection, self::FONT_DEFAULT, 0, self::ALIGN_BLOCK);
        $this->addSpace(8);
        $this->addText('letter_of_authorization_pdf_paragraph_1', 'en', $this->widthContentSection, self::FONT_ITALIC, 0, self::ALIGN_BLOCK);
        $this->addSpace(5);
        $this->addText('letter_of_authorization_pdf_paragraph_2', 'en', $this->widthContentSection, self::FONT_ITALIC, 0, self::ALIGN_BLOCK);
        $this->addSpace(8);

        $y = $this->GetY();
        $this->addText('Authorized person', 'de', $this->widthContentSection);
        $this->addText('Authorized person', 'en', $this->widthContentSection, self::FONT_ITALIC);
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection, $y);
        $this->addText($this->authorizedName, 'de', $widthHalfContentSection);
        $this->SetX($this->printBorderLeft);
        $this->addSpace(10);

        $y = $this->GetY();
        $this->addText('Granted until', 'de', $this->widthContentSection, self::FONT_BOLD);
        $this->addText('Granted until', 'en', $this->widthContentSection, self::FONT_ITALIC);
        $this->SetXY($this->printBorderLeft + $widthHalfContentSection, $y);
        $this->addText($this->grantedUntil, 'de', $widthHalfContentSection, self::FONT_BOLD);
        $this->SetX($this->printBorderLeft);
        $this->addSpace(20);

        $this->addText('Date', 'de', $this->widthContentSection);
        $y = $this->GetY();
        $this->addText('Date', 'en', $this->widthContentSection, self::FONT_ITALIC);
        $this->Line($this->printBorderLeft + 25, $y, $this->printBorderLeft + $this->widthContentSection, $y);
        $this->addSpace(10);

        $this->addText('Signature', 'de', $this->widthContentSection);
        $y = $this->GetY();
        $this->addText('Signature', 'en', $this->widthContentSection, self::FONT_ITALIC);
        $this->Line($this->printBorderLeft + 25, $y, $this->printBorderLeft + $this->widthContentSection, $y);
    }

    protected function addText(
        string $text,
        string $language = 'de',
        int $contentWidth = 0,
        string $textFontStyle = self::FONT_DEFAULT,
        int $textFontSize = 0,
        string $align = self::ALIGN_LEFT,
        int $lineMargin = 0
    ) : void {
        $this->addTextWithToken($text, $language, $contentWidth, [], $textFontStyle, $textFontSize, $align, $lineMargin);
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
    protected function addTextWithToken(
        string $text,
        string $language = 'de',
        int $contentWidth = 0,
        array $token = [],
        string $textFontStyle = self::FONT_DEFAULT,
        int $textFontSize = 0,
        string $align = self::ALIGN_LEFT,
        int $lineMargin = 0
    ) : void {
        if($contentWidth <= 0) {
            $contentWidth = $this->contentMaxWidth;
        }

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
        if($align != 'B') {
            $this->MultiCell($contentWidth, $this->FontSize + $lineMargin, trim($text), 0, $align);
        }
        else {
            $this->BlockCell(trim($text), $contentWidth,  $this->FontSize + $lineMargin);
        }
        $this->SetXY($x, $this->GetY());

        // restore font size
        $this->SetFont($this->FontFamily, $tmpFontStyle, $tmpFontSize);
    }

    private function BlockCell($text, $width, $height) {
        $minSpace = 0.05 * $this->FontSizePt;
        $words = explode(' ', $text);

        $lineWords = array();
        $lineWidth = 0;
        while(count($words) > 0) {
            $word = array_shift($words);
            $wordWidth = $this->GetStringWidth($word);
            if($lineWidth + $wordWidth + $minSpace >= $width) {
                $deltaWidth = $width - $lineWidth;
                $deltaSpace = $deltaWidth / (count($lineWords) - 1);

                foreach($lineWords as $lineWord) {
                    $lineWordWidth = $this->GetStringWidth($lineWord);
                    $this->Cell($lineWordWidth + $minSpace + $deltaSpace, $height, $lineWord);
                }

                $lineWords = array();
                $lineWidth = 0;

                $this->ln($height);
            }

            $lineWords[] = $word;
            $lineWidth += $minSpace + $wordWidth;
        }

        $this->Cell(0, $height, implode(' ', $lineWords));
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
     * @return LetterOfAuthorization
     */
    public function setAuthorizedName(string $authorizedName): LetterOfAuthorization {
        $this->authorizedName = $authorizedName;
        return $this;
    }

    /**
     * @param string $grantedUntil
     * @return LetterOfAuthorization
     */
    public function setGrantedUntil(string $grantedUntil): LetterOfAuthorization {
        $this->grantedUntil = $grantedUntil;
        return $this;
    }

    /**
     * @param string $issuerEMail
     * @return LetterOfAuthorization
     */
    public function setIssuerEMail(string $issuerEMail): LetterOfAuthorization {
        $this->issuerEMail = $issuerEMail;
        return $this;
    }

    /**
     * @param string $issuerName
     * @return LetterOfAuthorization
     */
    public function setIssuerName(string $issuerName): LetterOfAuthorization {
        $this->issuerName = $issuerName;
        return $this;
    }

    /**
     * @param string $issuerUserNumber
     * @return LetterOfAuthorization
     */
    public function setIssuerUserNumber(string $issuerUserNumber): LetterOfAuthorization {
        $this->issuerUserNumber = $issuerUserNumber;
        return $this;
    }

    /**
     * @param string[] $issuerAddress
     * @return LetterOfAuthorization
     */
    public function setIssuerAddress(array $issuerAddress): LetterOfAuthorization {
        $this->issuerAddress = $issuerAddress;
        return $this;
    }

    /**
     * @param mixed $barcode
     * @return LetterOfAuthorization
     */
    public function setBarcode($barcode): LetterOfAuthorization {
        $this->barcode = $barcode;
        return $this;
    }

    protected function getTranslatedStringWidth(string $string, string $language = 'de') : float {
        $originalLanguage = $this->translator->getTranslator()->getLocale();
        $this->translator->getTranslator()->setLocale($language);
        $translatedString = $this->translator->translateWithPrefix(self::TRANSLATION_PREFIX, $string);
        $this->translator->getTranslator()->setLocale($originalLanguage);

        return $this->GetStringWidth($translatedString);
    }
}