<?php

namespace App\Support;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PdfRenderer
{
    public static function downloadView(string $view, array $data, string $filename, array $options = [])
    {
        $html = view($view, $data)->render();

        return response(self::render($html, $options), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public static function streamView(string $view, array $data, string $filename, array $options = [])
    {
        $html = view($view, $data)->render();

        return response(self::render($html, $options), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public static function output(string $html, array $options = []): string
    {
        return self::render($html, $options);
    }

    private static function render(string $html, array $options = []): string
    {
        $mpdf = self::make($options);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private static function make(array $options = []): Mpdf
    {
        $cachePath = storage_path('framework/cache/mpdf');

        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0775, true);
        }

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf(array_merge([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => $cachePath,
            'fontDir' => array_merge($fontDirs, [
                public_path('assets/fonts'),
            ]),
            'fontdata' => $fontData + [
                'lateef' => [
                    'R' => 'LateefRegOT.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
                'xbriyaz' => [
                    'R' => 'XB Riyaz.ttf',
                    'B' => 'XB RiyazBd.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ], $options));

        $mpdf->SetDirectionality($options['directionality'] ?? 'ltr');
        $mpdf->showImageErrors = false;

        return $mpdf;
    }
}
