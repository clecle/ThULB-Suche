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
    protected $widthCallNumberIndex = 50;
    protected $heightCallNumberIndex = 100;

    // Form data
    protected $callNumber;
    protected $comment;
    protected $email;
    protected $name;
    protected $title;
    protected $username;
    protected $volume;
    protected $year;

    // Description keys
    protected $descCallNumber = "Call Number";
    protected $descComment = "Note";
    protected $descEmail = "Email";
    protected $descName = "Name";
    protected $descTitle = "Title";
    protected $descUsername = "Username";
    protected $descVolume = "storage_retrieval_request_volume";
    protected $descYear = "storage_retrieval_request_year";

    protected $translator;

    public function __construct(Translate $translator, $orientation = 'P', $unit = 'mm', $size = 'A4') {
        parent::__construct($orientation, $unit, $size);

        $this->translator = $translator;
    }

    public function create() {
        $globalLocale = $this->translator->getTranslator()->getLocale();
        $this->translator->getTranslator()->setLocale('de');

        $this->AddPage();
        $this->SetFont('Arial', '', 8);
        $this->SetMargins($this->printBorder, $this->printBorder);
        $this->SetAutoPageBreak(true, 0);

        $this->addLines();
        $this->addCardBook();
        $this->addCardUser();

        $this->addCardCallNumber();

        $this->translator->getTranslator()->setLocale($globalLocale);
    }

    protected function addLines() {
        $this->Line(
            $this->widthCallNumberIndex, 0,
            $this->widthCallNumberIndex, $this->dinA4height
        );
        $this->Line(
            0, $this->dinA4height - $this->heightCallNumberIndex,
            $this->widthCallNumberIndex, $this->dinA4height - $this->heightCallNumberIndex
        );
    }

    protected function addHeadLine($headline, $x, $y, $width, $spaceAtBottom = 0) {
        $this->SetXY($x, $y);
        $this->SetFont($this->FontFamily, 'UIB', $this->FontSizePt + 3);
        $this->MultiCell($width, $this->FontSize, $headline);
        $this->SetFont($this->FontFamily, '', $this->FontSizePt - 3);
        $this->SetXY($x, $y + $spaceAtBottom);
    }

    protected function addCardUser() {
        $availableTextWidth = $this->dinA4width - $this->widthCallNumberIndex - $this->printBorder * 2;

        $x = $this->widthCallNumberIndex + $this->printBorder;
        $this->addHeadLine('In Nutzerkartei', $x, $this->printBorder, $availableTextWidth, 10);

        $this->addText($this->descTitle,      $this->title,      $availableTextWidth, true);
        $this->addText($this->descEmail,      $this->email,      $availableTextWidth, true);
        $this->addText($this->descName,       $this->name,       $availableTextWidth, true);
        $this->addText($this->descUsername,   $this->username,   $availableTextWidth, true);
        $this->addText($this->descCallNumber, $this->callNumber, $availableTextWidth, true);
        $this->addText($this->descYear,       $this->year,       $availableTextWidth, true);
        $this->addText($this->descVolume,     $this->volume,     $availableTextWidth, true);
        $this->addText($this->descComment,    $this->comment,    $availableTextWidth, true);
    }

    protected function addCardCallNumber() {
        $availableTextWidth = $this->widthCallNumberIndex - $this->printBorder * 2;

        $y = $this->dinA4height - $this->heightCallNumberIndex + $this->printBorder;
        $this->addHeadLine('In Signaturenkartei', $this->printBorder, $y, $availableTextWidth, 10);

        $this->addText($this->descCallNumber, $this->callNumber, $availableTextWidth);
        $this->addText($this->descName,       $this->name,       $availableTextWidth);
        $this->addText($this->descUsername,   $this->username,   $availableTextWidth);
        $this->addText($this->descTitle,      $this->title,      $availableTextWidth);
        $this->addText($this->descEmail,      $this->email,      $availableTextWidth);
        $this->addText($this->descYear,       $this->year,       $availableTextWidth);
        $this->addText($this->descVolume,     $this->volume,     $availableTextWidth);
    }

    protected function addCardBook() {
        $availableTextWidth = $this->widthCallNumberIndex - $this->printBorder * 2;

        $this->addHeadLine('Ins Buch', $this->printBorder, $this->printBorder, $availableTextWidth, 10);

        $this->addText($this->descTitle,      $this->title,      $availableTextWidth);
        $this->addText($this->descEmail,      $this->email,      $availableTextWidth);
        $this->addText($this->descName,       $this->name,       $availableTextWidth);
        $this->addText($this->descUsername,   $this->username,   $availableTextWidth);
        $this->addText($this->descCallNumber, $this->callNumber, $availableTextWidth);
        $this->addText($this->descYear,       $this->year,       $availableTextWidth);
        $this->addText($this->descVolume,     $this->volume,     $availableTextWidth);
    }

    protected function addText($description, $text, $cellWidth, $asTable = false) {
        $description = $this->translator->translate($description) . ':';
        $spaceBetweenLines = 1;

        $x = $this->GetX();
        $y = $this->GetY() + 2;
        $this->SetXY($x, $y);

        $this->SetFont($this->FontFamily, 'UB');
        $this->MultiCell($cellWidth, $this->FontSize + $spaceBetweenLines, utf8_decode($description));
        $this->SetXY(
            !$asTable ? $x : $x + 30,
            !$asTable ? $this->GetY() : $y
        );

        $this->SetFont($this->FontFamily, '');
        $this->MultiCell($cellWidth, $this->FontSize + $spaceBetweenLines, utf8_decode($text));
        $this->SetXY($x, $this->GetY());
    }

    public function setWorkTitle ($title) {
        $this->title = $title;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setName($username) {
        $this->name = $username;
    }

    public function setUserName($userId) {
        $this->username = $userId;
    }

    public function setCallNumber($callNumber) {
        $this->callNumber = $callNumber;
    }

    public function setYear($year) {
        $this->year = $year;
    }

    public function setVolume($volume) {
        $this->volume = $volume;
    }

    public function setComment($comment) {
        $this->comment = $comment;
    }
}