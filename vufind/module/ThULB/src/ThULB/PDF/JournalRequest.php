<?php

namespace ThULB\PDF;

use FPDF;
use VuFind\View\Helper\Root\Translate;
use Zend\Mvc\I18n\Translator;

class JournalRequest extends FPDF
{
    // All Dimensions in 'mm'
    protected $dinA4width = 210;
    protected $dinA4height = 297;
    protected $printBorder = 5;
    protected $widthCallNumberCard = 50;
    protected $heightCallNumberCard = 100;
    protected $heightUserCard = 120;
    protected $widthBookCard = 50;

    // Form data
    protected $callNumber;
    protected $comment;
    protected $issue;
    protected $name;
    protected $requestPages;
    protected $title;
    protected $username;
    protected $volume;
    protected $year;

    // Description keys
    protected $descCallNumber = 'Call Number';
    protected $descComment = 'Note';
    protected $descIssue = 'Issue';
    protected $descName = 'Name';
    protected $descPages = 'storage_retrieval_request_page(s)';
    protected $descTitle = 'Title';
    protected $descVolume = 'storage_retrieval_request_volume';
    protected $descYear = 'storage_retrieval_request_year';

    protected const DEFAULT_FONT_SIZE = 10;

    protected $translator;

    /**
     * Constructor.
     *
     * @param Translate $translator Translator to use.
     * @param string $orientation Page orientation.
     * @param string $unit Unit to measure pages.
     * @param string $size Size of the pages.
     */
    public function __construct(Translate $translator, $orientation = 'P', $unit = 'mm', $size = 'A4') {
        parent::__construct($orientation, $unit, $size);

        $this->translator = $translator;
    }

    /**
     * Create the request pdf. Data must be set beforehand.
     */
    public function create() {
        $globalLocale = $this->translator->getTranslator()->getLocale();
        $this->translator->getTranslator()->addTranslationFile('ExtendedIni', null, 'default', 'de');
        $this->translator->getTranslator()->setLocale('de');

        $this->AddPage();
        $this->SetFont('Arial', '', self::DEFAULT_FONT_SIZE);
        $this->SetMargins($this->printBorder, $this->printBorder);
        $this->SetAutoPageBreak(true, 0);

        // Add 'test' as text.
        $this->SetTextColor(240);
        $this->SetFontSize(100);
        $this->SetXY(0, 0);
        $this->Cell($this->dinA4width, $this->dinA4height, 'T E S T ', 0, 0, 'C');
        $this->SetFont('Arial', '', self::DEFAULT_FONT_SIZE);
        $this->SetTextColor(0);

        $this->addLines();
        $this->addCardBook();
        $this->addCardUser();

        $this->addCardCallNumber();

        $this->translator->getTranslator()->setLocale($globalLocale);
    }

    /**
     * Add vertical and horizontal separation lines to the pdf.
     */
    protected function addLines() {
        // card for books
        $this->Line(
            $this->widthBookCard, 0,
            $this->widthBookCard, $this->dinA4height
        );

        // card for users
        $this->line(
            $this->widthBookCard, $this->heightUserCard,
            $this->dinA4width, $this->heightUserCard
        );

        // card for callnumbers
        $this->Line(
            $this->dinA4width - $this->widthCallNumberCard, $this->dinA4height - $this->heightCallNumberCard,
            $this->dinA4width, $this->dinA4height - $this->heightCallNumberCard
        );
        $this->line(
            $this->dinA4width - $this->widthCallNumberCard, $this->dinA4height - $this->heightCallNumberCard,
            $this->dinA4width - $this->widthCallNumberCard, $this->dinA4height
        );
    }

    /**
     * Add a text formatted as headline.
     * Sets XY coordinates for the next lines to be added.
     *
     * @param string $headline Headline text
     * @param int $x X coordinate of the headline.
     * @param int $y Y coordinate of the headline.
     * @param int $width Width of the headline.
     * @param int $spaceAtBottom Space between headline and the next text.
     */
    protected function addHeadLine($headline, $x, $y, $width, $spaceAtBottom = 0) {
        $this->SetXY($x, $y);
        $this->SetFont($this->FontFamily, 'UB', $this->FontSizePt + 3);
        $this->MultiCell($width, $this->FontSize, $headline);
        $this->SetFont($this->FontFamily, '', $this->FontSizePt - 3);
        $this->SetXY($x, $y + $spaceAtBottom);
    }

    /**
     * Write information for the user card to the pdf.
     */
    protected function addCardUser() {
        $availableTextWidth = $this->dinA4width - $this->widthCallNumberCard - $this->printBorder * 2;

        $title = $this->shortenTextForWidth($this->title, $availableTextWidth - 35);

        $x = $this->widthCallNumberCard + $this->printBorder;
        $this->addHeadLine('Begleitzettel (freie Bestellbarkeit)', $x, $this->printBorder, $availableTextWidth, 16);

        $this->addText($this->descName,       $this->name,         $availableTextWidth, true);
        $this->addText("Benutzernr.",         $this->username,     $availableTextWidth, true);

        $this->SetXY($x, $this->GetY() + 10);

        $this->addText($this->descCallNumber, $this->callNumber,   $availableTextWidth, true);
        $this->addText($this->descTitle,      $title,              $availableTextWidth, true);
        $this->addText($this->descYear,       $this->year,         $availableTextWidth, true);
        $this->addText($this->descVolume,     $this->volume,       $availableTextWidth, true);
        $this->addText($this->descIssue,      $this->issue,        $availableTextWidth, true);
        $this->addText($this->descPages,      $this->requestPages, $availableTextWidth, true);
        $this->addText($this->descComment,    $this->comment,      $availableTextWidth, true);
    }

    /**
     * Write information for the callnumber card to the pdf.
     */
    protected function addCardCallNumber() {
        $availableTextWidth = $this->widthCallNumberCard - $this->printBorder * 2;

        $title = $this->shortenTextForWidth($this->title, $availableTextWidth, 2);

        $x = $this->dinA4width - $this->widthCallNumberCard + $this->printBorder;
        $y = $this->dinA4height - $this->heightCallNumberCard + $this->printBorder;
        $this->SetXY($x, $y);

        $this->addText($this->descCallNumber, $this->callNumber,   $availableTextWidth,
                      false, 'B', self::DEFAULT_FONT_SIZE + 2);
        $this->addText($this->descName,       $this->name,         $availableTextWidth);
        $this->addText("Benutzernr.",         $this->username,     $availableTextWidth);
        $this->addText($this->descTitle,      $title,              $availableTextWidth);
        $this->addText($this->descYear,       $this->year,         $availableTextWidth);
        $this->addText($this->descVolume,     $this->volume,       $availableTextWidth);
        $this->addText($this->descIssue,      $this->issue,        $availableTextWidth);
        $this->addText($this->descPages,      $this->requestPages, $availableTextWidth);
    }

    /**
     * Write information for the book card to the pdf.
     */
    protected function addCardBook() {
        $availableTextWidth = $this->widthCallNumberCard - $this->printBorder * 2;

        $title = $this->shortenTextForWidth($this->title, $availableTextWidth, 2);

        $this->SetXY($this->printBorder, $this->printBorder);

        $this->addText($this->descCallNumber, $this->callNumber,   $availableTextWidth);
        $this->addText($this->descTitle,      $title,              $availableTextWidth);
        $this->addText($this->descYear,       $this->year,         $availableTextWidth);
        $this->addText($this->descVolume,     $this->volume,       $availableTextWidth);
        $this->addText($this->descIssue,      $this->issue,        $availableTextWidth);
        $this->addText($this->descPages,      $this->requestPages, $availableTextWidth);
        $this->addText("bearbeitet am",       null,                $availableTextWidth);

        $this->SetXY($this->printBorder, $this->dinA4height - 50);

        $this->addText("Leihfrist",           null,                $availableTextWidth);
        $this->addText("Benutzernr.",         $this->username,     $availableTextWidth);
        $this->addText($this->descName,       $this->name,         $availableTextWidth, false, 'B');
    }

    /**
     * Adds a text to the pdf. X, Y coordinates have to be set beforehand.
     *
     * @param string $description Translation key of the description.
     * @param string $text Text to be added.
     * @param int $cellWidth Width of the text cell.
     * @param bool $asTable Format text as a table? If true, there will be a column
     *                            for descriptions and a column for the text.
     * @param string $textFontStyle
     * @param int $textFontSize
     */
    protected function addText($description, $text, $cellWidth, $asTable = false, $textFontStyle = '', $textFontSize = self::DEFAULT_FONT_SIZE) {
        $description = $this->translator->translate($description);
        $description = $description ? $description . ':' : '';
        $spaceBetweenLines = 1;

        $tableOffset = $asTable ? 25 : 0;
        $x = $this->GetX();
        $y = $this->GetY() + 2;
        $this->SetXY($x, $y);

        // save font style and size to restore it after adding the text
        $tmpFontSize = $this->FontSizePt;
        $tmpFontStyle = $this->FontStyle;

        $this->SetFont($this->FontFamily, 'B');
        $this->MultiCell($cellWidth, $this->FontSize + $spaceBetweenLines, utf8_decode($description));
        $this->SetXY(
            !$asTable ? $x : $x + $tableOffset,
            !$asTable ? $this->GetY() : $y
        );

        $stringWidth = $this->GetStringWidth($text);

        $this->SetFont($this->FontFamily, $textFontStyle, $textFontSize);
        $this->MultiCell($cellWidth - $tableOffset, $this->FontSize + $spaceBetweenLines, utf8_decode($text), 0, 'L');
        $this->SetXY($x, $this->GetY());

        // restore font style and size
        $this->SetFont($this->FontFamily, $tmpFontStyle, $tmpFontSize);
    }

    /**
     * Set title data.
     *
     * @param string $title
     */
    public function setWorkTitle ($title) {
        $this->title = $title;
    }

    /**
     * Shortens a text to fit in a specified width.
     *
     * @param string $string      Text to shorten.
     * @param int    $widthInMM   Max width for the text after shortening.
     * @param int    $wantedLines Lines the text can fill.
     *
     * @return string
     */
    protected function shortenTextForWidth($string, $widthInMM, $wantedLines = 1) {
        // Base functionality taken from fpdf::MultiCell
        $widthMax = ($widthInMM - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $charWidth = &$this->CurrentFont['cw'];
        $result = "";

        $numberBytes = strlen($string);
        $indexSeparator = -1;
        $indexString = 0;
        $j = 0;
        $length = 0;
        $numberSeparators = 0;
        $numberLines = 1;
        while($indexString < $numberBytes) {
            if($numberLines > $wantedLines) {
                break;
            }

            // Get next character
            $char = $string[$indexString];
            if($numberLines < $wantedLines && $char == ' ') {
                $indexSeparator = $indexString;
                $numberSeparators++;
            }

            $length += $charWidth[$char];
            if($length > $widthMax) {
                // Automatic line break
                if($indexSeparator == -1) {
                    if($indexString == $j) {
                        $indexString++;
                    }
                    $result .= substr($string, $j, $indexString - $j);
                }
                else {
                    $result .= substr($string, $j,$indexSeparator - $j + 1);
                    $indexString = $indexSeparator + 1;
                }
                $indexSeparator = -1;
                $j = $indexString;
                $length = 0;
                $numberSeparators = 0;
                $numberLines++;
            }
            else {
                $indexString++;
            }
        }

        // Replace the last three chars with 3 dots if the text was shortened.
        if($numberBytes > strlen($result)) {
            $result = substr($result, 0 , -3) . "...";
        }

        return $result;
    }

    /**
     * Set issue data.
     *
     * @param string $issue
     */
    public function setIssue($issue) {
        $this->issue = $issue;
    }

    /**
     * Set pages data.
     *
     * @param string $pages
     */
    public function setPages($pages) {
        $this->requestPages = $pages;
    }

    /**
     * Set name data.
     *
     * @param string $username
     */
    public function setName($username) {
        $this->name = $username;
    }

    /**
     * Set user id data.
     *
     * @param string $userId
     */
    public function setUserName($userId) {
        $this->username = $userId;
    }

    /**
     * Set callnumber data.
     *
     * @param string $callNumber
     */
    public function setCallNumber($callNumber) {
        $this->callNumber = $callNumber;
    }

    /**
     * Set year data.
     *
     * @param string $year
     */
    public function setYear($year) {
        $this->year = $year;
    }

    /**
     * Set volume data.
     *
     * @param string $volume
     */
    public function setVolume($volume) {
        $this->volume = $volume;
    }

    /**
     * Set comment data.
     *
     * @param string $comment
     */
    public function setComment($comment) {
        $this->comment = $comment;
    }
}