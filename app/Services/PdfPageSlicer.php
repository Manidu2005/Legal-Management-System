<?php

namespace App\Services;

use RuntimeException;
use setasign\Fpdi\Fpdi;

class PdfPageSlicer
{
    /**
     * Extract pages [$startPage, $endPage] (1-based, inclusive) into a new PDF.
     *
     * @return string Raw PDF bytes of the slice
     */
    public function slice(string $sourceAbsolutePath, int $startPage, int $endPage): string
    {
        if ($startPage < 1 || $endPage < $startPage) {
            throw new RuntimeException("Invalid page range {$startPage}-{$endPage}.");
        }

        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($sourceAbsolutePath);

        if ($endPage > $pageCount) {
            throw new RuntimeException(
                "End page {$endPage} exceeds source page count {$pageCount}."
            );
        }

        for ($page = $startPage; $page <= $endPage; $page++) {
            $templateId = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        return $pdf->Output('S');
    }
}
