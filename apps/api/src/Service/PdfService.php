<?php

declare(strict_types=1);

namespace Guard51\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;

final class PdfService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function generateFromHtml(string $html, string $paperSize = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options();
        $options->setIsRemoteEnabled(true);
        $options->setDefaultFont('sans-serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paperSize, $orientation);
        $dompdf->render();

        $this->logger->debug('PDF generated.', ['size' => $paperSize, 'orientation' => $orientation]);

        return $dompdf->output();
    }

    public function generateAndSave(string $html, string $filePath, string $paperSize = 'A4'): bool
    {
        $pdfContent = $this->generateFromHtml($html, $paperSize);
        $result = file_put_contents($filePath, $pdfContent);
        return $result !== false;
    }
}
