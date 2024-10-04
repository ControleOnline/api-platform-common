<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{


    public function showPdf($content)
    {
        $response = new StreamedResponse(function () use ($content) {
            fputs(fopen('php://output', 'wb'), $content);
        });

        $response->headers->set('Content-Type', 'application/pdf');

        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, 'contract.pdf');

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    public function convertHtmlToPdf($html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $html = $dompdf->output();

        return $html;
    }
}
