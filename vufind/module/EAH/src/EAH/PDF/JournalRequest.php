<?php

namespace EAH\PDF;

use ThULB\PDF\JournalRequest as OriginalPDF;
use VuFind\View\Helper\Root\Translate;

class JournalRequest extends OriginalPDF
{
    // All Dimensions in 'mm'
    protected int $dinA5width = 148;
    protected int $dinA5height = 210;
    protected int $printBorder = 13;

    // Form data
    protected string $location;

    // Description keys
    protected string $descOrderedAt = 'Bestelldatum:';

    protected const DEFAULT_FONT_SIZE = 12;

    protected Translate $translator;

    /**
     * Constructor.
     *
     * @param Translate $translator  Translator to use.
     * @param string    $orientation Page orientation.
     * @param string    $unit        Unit to measure pages.
     * @param string    $size        Size of the pages.
     */
    public function __construct(Translate $translator, $orientation = 'P', $unit = 'mm', string $size = 'A5') {
        parent::__construct($translator, $orientation, $unit, $size);
    }

    /**
     * Create the request pdf. Data must be set beforehand.
     */
    public function create() : void {
        $globalLocale = $this->translator->getTranslator()->getLocale();
        $this->translator->getTranslator()->addTranslationFile('ExtendedIni', null, 'default', 'de');
        $this->translator->getTranslator()->setLocale('de');

        $this->fontpath = APPLICATION_PATH . '/themes/thulb/fonts/';
        $this->AddPage();
        $this->AddFont('Roboto', '',  'Roboto-Regular.ttf', true);
        $this->AddFont('Roboto', 'B', 'Roboto-Bold.ttf', true);
        $this->AddFont('Roboto', 'I', 'Roboto-Italic.ttf', true);
        $this->SetFont('Roboto', '',  self::DEFAULT_FONT_SIZE);

        $this->SetMargins($this->printBorder, $this->printBorder);
        $this->SetAutoPageBreak(true, 0);

        $this->addContent();

        $this->translator->getTranslator()->setLocale($globalLocale);
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
        $this->SetFont($this->FontFamily, 'B', $this->FontSizePt + 8);
        $this->MultiCell($width, $this->FontSize, $headline, 0, 'C');
        $this->SetFont($this->FontFamily, '', $this->FontSizePt - 8);
        $this->SetXY($x, $y + $spaceAtBottom);
    }

    /**
     * Write information for the user card to the pdf.
     */
    protected function addContent() : void {
        $availableTextWidth = $this->dinA5width - $this->printBorder * 2;

        // add headline
        $this->SetXY($this->printBorder, 10);
        $this->SetFont($this->FontFamily, 'B', 18);
        $this->MultiCell($availableTextWidth, $this->FontSize, 'Begleitzettel', 0, 'C');

        // add sub headline
        $this->SetXY($this->printBorder, $this->GetY() + 2);
        $this->SetFont($this->FontFamily, '', 11);
        $this->MultiCell($availableTextWidth, $this->FontSize, 'Freie Bestellung (' . $this->location . ')', 0, 'C');

        $this->SetFont($this->FontFamily, '', static::DEFAULT_FONT_SIZE);

        $title = $this->shortenTextForWidth($this->title, $availableTextWidth - 25);

        $this->SetXY($this->printBorder, $this->GetY() + 10);

        $this->addText($this->descCallNumber, $this->callNumber,   $availableTextWidth, true);
        $this->SetXY($this->printBorder, $this->GetY() + 5);
        $this->addText($this->descTitle, $title, $availableTextWidth, true);
        $this->SetXY($this->printBorder, $this->GetY() + 5);
        $this->addText($this->descYear, $this->year, $availableTextWidth, true);
        $this->SetXY($this->printBorder, $this->GetY() + 5);
        $this->addText($this->descVolume, $this->volume, $availableTextWidth, true);
        $this->SetXY($this->printBorder, $this->GetY() + 5);
        $this->addText($this->descIssue, $this->issue, $availableTextWidth, true);
        $this->SetXY($this->printBorder, $this->GetY() + 5);
        $this->addText($this->descPages, $this->requestPages, $availableTextWidth, true);
        $this->SetXY($this->printBorder, $this->GetY() + 5);
        $this->addText($this->descComment, $this->comment, $availableTextWidth, true);

        $this->SetXY($this->printBorder, 166);

        $y = $this->GetY();
        $this->MultiCell(30, $this->FontSize, $this->descOrderedAt);

        $this->SetXY($this->printBorder + 30, $y);
        $this->MultiCell($availableTextWidth - 30, $this->FontSize, $this->orderedAt);

        $this->SetXY($this->printBorder, $this->GetY() + 10);
        $this->MultiCell($availableTextWidth, $this->FontSize, $this->username);

        $this->SetFont($this->FontFamily, 'B', static::DEFAULT_FONT_SIZE);
        $this->SetXY($this->printBorder, $this->GetY() + 5);
        $this->MultiCell($availableTextWidth, $this->FontSize, $this->lastname);
        $this->MultiCell($availableTextWidth, $this->FontSize, $this->firstname);
    }

    public function setLocation(string $location) {
        $this->location = $location;
    }
}