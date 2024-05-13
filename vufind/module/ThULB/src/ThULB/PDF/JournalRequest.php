<?php

namespace ThULB\PDF;

use tFPDF;
use VuFind\View\Helper\Root\Translate;

class JournalRequest extends tFPDF
{
    // All Dimensions in 'mm'
    protected int $dinA4width = 210;
    protected int $dinA4height = 297;
    protected int $printBorder = 5;
    protected int $widthCallNumberCard = 50;
    protected int $heightCallNumberCard = 105;
    protected int $heightUserCard = 150;
    protected int $widthBookCard = 50;

    // Form data
    protected string $callNumber;
    protected string $comment;
    protected string $issue;
    protected string $firstname;
    protected string $lastname;
    protected string $requestPages;
    protected string $title;
    protected string $username;
    protected string $volume;
    protected string $year;
    protected string $orderedAt;

    // Description keys
    protected string $descCallNumber = 'Call Number';
    protected string $descComment = 'Note';
    protected string $descIssue = 'Issue';
    protected string $descName = 'Name';
    protected string $descPages = 'storage_retrieval_request_page(s)';
    protected string $descTitle = 'Title';
    protected string $descVolume = 'storage_retrieval_request_volume';
    protected string $descYear = 'storage_retrieval_request_year';
    protected string $descUserNumber = "Benutzernr.";
    protected string $descOrderedAt = "bestellt am";

    protected const DEFAULT_FONT_SIZE = 10;

    protected Translate $translator;

    /**
     * Constructor.
     *
     * @param Translate $translator  Translator to use.
     * @param string    $orientation Page orientation.
     * @param string    $unit        Unit to measure pages.
     * @param string    $size        Size of the pages.
     */
    public function __construct(Translate $translator, $orientation = 'P', $unit = 'mm', string $size = 'A4') {
        parent::__construct($orientation, $unit, $size);

        $this->translator = $translator;

        $this->orderedAt = date('d.m.Y');
    }

    /**
     * Create the request pdf. Data must be set beforehand.
     */
    public function create() : void {
        $globalLocale = $this->translator->getTranslator()->getLocale();
        $this->translator->getTranslator()->addTranslationFile('ExtendedIni', null, 'default', 'de');
        $this->translator->getTranslator()->setLocale('de');

        $this->AddPage();
        $this->AddFont('DejaVu', '',  'DejaVuSansCondensed.ttf',true);
        $this->AddFont('DejaVu', 'B', 'DejaVuSansCondensed-Bold.ttf',true);
        $this->SetFont('DejaVu', '',  self::DEFAULT_FONT_SIZE);

        $this->SetMargins($this->printBorder, $this->printBorder);
        $this->SetAutoPageBreak(true);

        $this->addLines();
        $this->addCardBook();
        $this->addCardUser();

        $this->addCardCallNumber();

        $this->translator->getTranslator()->setLocale($globalLocale);
    }

    /**
     * Add vertical and horizontal separation lines to the pdf.
     */
    protected function addLines() : void {
        $this->SetDrawColor(180);
        // card for books
        $this->Line(
            $this->widthBookCard, 0,
            $this->widthBookCard, $this->dinA4height
        );

        // card for users
        $this->Line(
            $this->widthBookCard, $this->heightUserCard,
            $this->dinA4width, $this->heightUserCard
        );

        // card for call numbers
        $this->stripedLine(
            $this->dinA4width - $this->widthCallNumberCard, $this->heightCallNumberCard,
            $this->dinA4width, $this->heightCallNumberCard
        );
        $this->Line(
            $this->dinA4width - $this->widthCallNumberCard, 0,
            $this->dinA4width - $this->widthCallNumberCard, $this->heightUserCard
        );
        $this->SetDrawColor(0);
    }

    /**
     * Draw a dotted line.
     *
     * @param int $x1     X coordinate of the start point.
     * @param int $y1     Y coordinate of the start point.
     * @param int $x2     X coordinate of the end point.
     * @param int $y2     Y coordinate of the end point.
     * @param int $dashes Amount of dashes in this line, affects width of dashes
     */
    protected function stripedLine(int $x1, int $y1, int $x2, int $y2, int $dashes = 10) : void {
        $segmentWidth = ($x2 - $x1) / ($dashes * 2 - 1);
        for($segment = 0; $segment < ($dashes * 2 - 1); $segment++) {
            if($segment % 2) {
                continue;
            }

            $segmentOffset = $segment * $segmentWidth;
            $this->Line(
                $x1 + $segmentOffset, $y1,
                $x1 + $segmentOffset + $segmentWidth, $y2
            );
        }
    }

    /**
     * Add a text formatted as headline.
     * Sets XY coordinates for the next lines to be added.
     *
     * @param string $headline      Headline text
     * @param int    $x             X coordinate of the headline.
     * @param int    $y             Y coordinate of the headline.
     * @param int    $width         Width of the headline.
     * @param int    $spaceAtBottom Space between headline and the next text.
     */
    protected function addHeadLine(string $headline, int $x, int $y, int $width, int $spaceAtBottom = 0) : void {
        $this->SetXY($x, $y);
        $this->SetFont($this->FontFamily, 'UB', $this->FontSizePt + 3);
        $this->MultiCell($width, $this->FontSize, $headline);
        $this->SetFont($this->FontFamily, '', $this->FontSizePt - 3);
        $this->SetXY($x, $y + $spaceAtBottom);
    }

    /**
     * Write information for the user card to the pdf.
     */
    protected function addCardUser() : void {
        $availableTextWidth =
            $this->dinA4width - $this->widthCallNumberCard - $this->widthCallNumberCard - $this->printBorder * 2;

        $title = $this->shortenTextForWidth($this->title, $availableTextWidth - 25);
        $name = $this->lastname . ', ' . $this->firstname;

        $x = $this->widthCallNumberCard + $this->printBorder;
        $this->addHeadLine('Begleitzettel (freie Bestellbarkeit)', $x, $this->printBorder, $availableTextWidth, 16);

        $this->addText($this->descName,       $name,               $availableTextWidth, true);
        $this->addText($this->descUserNumber, $this->username,     $availableTextWidth, true);
        $this->addText($this->descOrderedAt,  $this->orderedAt,    $availableTextWidth, true);

        $this->SetXY($x, $this->GetY() + 10);

        $this->addText($this->descCallNumber, $this->callNumber,   $availableTextWidth, true, 'B');
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
    protected function addCardCallNumber() : void {
        $availableTextWidth = $this->widthCallNumberCard - $this->printBorder * 2;

        $title = $this->shortenTextForWidth($this->title, $availableTextWidth, 2);
        $name = $this->lastname . ', ' . $this->firstname;

        $x = $this->dinA4width - $this->widthCallNumberCard + $this->printBorder;
        $y = $this->printBorder;
        $this->SetXY($x, $y);

        $this->addText("Leihfrist",           null,                $availableTextWidth);
        $this->SetXY($this->GetX(), $this->GetY() + 5);
        $this->addText($this->descCallNumber, $this->callNumber,   $availableTextWidth,
                      false, 'B', self::DEFAULT_FONT_SIZE + 2);
        $this->addText($this->descName,       $name,               $availableTextWidth);
        $this->addText($this->descUserNumber, $this->username,     $availableTextWidth);
        $this->addText($this->descOrderedAt,  $this->orderedAt,    $availableTextWidth);
        $this->addText($this->descTitle,      $title,              $availableTextWidth);
        $this->addText($this->descYear,       $this->year,         $availableTextWidth);
        $this->addText($this->descVolume,     $this->volume,       $availableTextWidth);
        $this->addText($this->descIssue,      $this->issue,        $availableTextWidth);
    }

    /**
     * Write information for the book card to the pdf.
     */
    protected function addCardBook() : void {
        $availableTextWidth = $this->widthCallNumberCard - $this->printBorder * 2;

        $title = $this->shortenTextForWidth($this->title, $availableTextWidth, 2);
        $name = $this->lastname . ', ' . $this->firstname;

        $this->SetXY($this->printBorder, $this->printBorder);

        $this->addText($this->descCallNumber, $this->callNumber,   $availableTextWidth);
        $this->addText($this->descTitle,      $title,              $availableTextWidth);
        $this->addText($this->descYear,       $this->year,         $availableTextWidth);
        $this->addText($this->descVolume,     $this->volume,       $availableTextWidth);
        $this->addText($this->descIssue,      $this->issue,        $availableTextWidth);
        $this->addText($this->descOrderedAt,  $this->orderedAt,    $availableTextWidth);
        $this->addText("bearbeitet am",       null,                $availableTextWidth);

        $this->SetXY($this->printBorder, $this->dinA4height - 60);

        $this->addText("Leihfrist",           null,                $availableTextWidth);
        $this->SetXY($this->GetX(), $this->GetY() + 5);
        $this->addText($this->descUserNumber, $this->username,     $availableTextWidth);
        $this->addText($this->descName,       $name,               $availableTextWidth,
                       false, 'B', self::DEFAULT_FONT_SIZE + 2);
    }

    /**
     * Adds a text to the pdf. X, Y coordinates have to be set beforehand.
     *
     * @param string      $description   Translation key of the description.
     * @param string|null $text          Text to be added.
     * @param int         $cellWidth     Width of the text cell.
     * @param bool        $asTable       Format text as a table? If true, there will be a column
     *                                   for descriptions and a column for the text.
     * @param string      $textFontStyle
     * @param int         $textFontSize
     */
    protected function addText(string $description, ?string $text, int $cellWidth, bool $asTable = false,
                               string $textFontStyle = '', int $textFontSize = 0) : void {
        if(!$textFontSize) {
            $textFontSize = static::DEFAULT_FONT_SIZE;
        }

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
        $this->MultiCell($cellWidth, $this->FontSize + $spaceBetweenLines, $description);
        $this->SetXY(
            $x + $tableOffset,
            !$asTable ? $this->GetY() : $y
        );

        $this->SetFont($this->FontFamily, $textFontStyle, $textFontSize);
        $this->MultiCell($cellWidth - $tableOffset, $this->FontSize + $spaceBetweenLines, $text, 0, 'L');
        $this->SetXY($x, $this->GetY());

        // restore font style and size
        $this->SetFont($this->FontFamily, $tmpFontStyle, $tmpFontSize);
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
    protected function shortenTextForWidth(string $string, int $widthInMM, int $wantedLines = 1) : string {
        // Base functionality taken from fpdf::MultiCell
        $widthMax = ($widthInMM - 2 * $this->cMargin);
        $charWidth = &$this->CurrentFont['cw'];
        $result = "";

        // Get string length
        $string = str_replace("\r", '', $string);
        $numberBytes = mb_strlen($string, 'utf-8');
        while($numberBytes > 0 && mb_substr($string, $numberBytes - 1, 1, 'utf-8') == "\n") {
            $numberBytes--;
        }

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
            $char = mb_substr($string, $indexString, 1, 'UTF-8');
            if($numberLines < $wantedLines && $char == ' ') {
                $indexSeparator = $indexString;
                $numberSeparators++;
            }

            $length += $this->GetStringWidth($char);

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

            if($indexString >= $numberBytes && $length <= $widthMax) {
                $result .= substr($string, $j, $indexString - $j);
            }
        }

        // Replace the last three chars with 3 dots if the text was shortened.
        if($numberBytes > strlen($result)) {
            $result = substr($result, 0 , -3) . "...";
        }

        return $result;
    }

    /**
     * Set title data.
     *
     * @param string $title
     */
    public function setWorkTitle (string $title) : void {
        $this->title = $title;
    }

    /**
     * Set issue data.
     *
     * @param string $issue
     */
    public function setIssue(string $issue) : void {
        $this->issue = $issue;
    }

    /**
     * Set pages data.
     *
     * @param string $pages
     */
    public function setPages(string $pages) : void {
        $this->requestPages = $pages;
    }

    /**
     * Set firstname data.
     *
     * @param string $firstname
     */
    public function setFirstName(string $firstname) : void {
        $this->firstname = $firstname;
    }

    /**
     * Set lastname data.
     *
     * @param string $lastname
     */
    public function setLastName(string $lastname) : void {
        $this->lastname = $lastname;
    }

    /**
     * Set user id data.
     *
     * @param string $userId
     */
    public function setUserName(string $userId) : void {
        $this->username = $userId;
    }

    /**
     * Set callnumber data.
     *
     * @param string $callNumber
     */
    public function setCallNumber(string $callNumber) : void {
        $this->callNumber = $callNumber;
    }

    /**
     * Set year data.
     *
     * @param string $year
     */
    public function setYear(string $year) : void {
        $this->year = $year;
    }

    /**
     * Set volume data.
     *
     * @param string $volume
     */
    public function setVolume(string $volume) : void {
        $this->volume = $volume;
    }

    /**
     * Set comment data.
     *
     * @param string $comment
     */
    public function setComment(string $comment) : void {
        $this->comment = $comment;
    }
}