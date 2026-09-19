<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    private Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser;
    }

    /**
     * Extract plain text from a PDF file on disk.
     */
    public function extractFromPath(string $absolutePath): string
    {
        $pdf = $this->parser->parseFile($absolutePath);
        $text = $pdf->getText();

        return trim(preg_replace("/[ \t]+/u", ' ', $text) ?? $text);
    }

    /**
     * Extract plain text from raw PDF bytes.
     */
    public function extractFromBytes(string $pdfBytes): string
    {
        $pdf = $this->parser->parseContent($pdfBytes);
        $text = $pdf->getText();

        return trim(preg_replace("/[ \t]+/u", ' ', $text) ?? $text);
    }
}
