<?php

namespace App\Http\Controllers;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
// use Endroid\QrCode\Writer\WebPWriter;
use Illuminate\Http\Request;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\URL;

class QrController extends Controller
{
    public function generarQR(Request $request)
    {
        try {
            $url = $request->query('url');

            if (!$url) {
                return response()->json(['error' => 'URL no proporcionada'], 400);
            }

            $writer = new SvgWriter();

            $qrCode = new QrCode(
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 0,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255),
            );

            $result = $writer->write($qrCode);

            return response($result->getString())
                ->header('Content-Type', $result->getMimeType());

        } catch (\Exception $e) {
            \Log::error('Error generando QR: ' . $e->getMessage());
            return response()->json(['error' => 'Error generando QR'], 500);
        }
    }
}
