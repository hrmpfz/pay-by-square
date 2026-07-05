<?php

namespace Hrmpfz\PayBySquare\Qr;

use Hrmpfz\PayBySquare\Payment;
use Hrmpfz\PayBySquare\Encoder;
use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\{QRGdImage, QRMarkupSVG};
use RuntimeException;

/**
 * Handles rendering of the payment payload into PNG/SVG QR codes.
 */
class Renderer
{
    /**
     * Renders the payment QR code as a PNG file.
     * Requires the GD extension.
     */
    public static function png(Payment $payment, string $outputPath): void
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException("The GD extension is required to generate PNG QR codes.");
        }

        $payload = Encoder::encode($payment);

        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'eccLevel' => EccLevel::L,
            'outputInterface' => QRGdImage::class,
            'scale' => 6,
            'imageBase64' => false,
        ]);

        $qrcode = new QRCode($options);
        $imageData = $qrcode->render($payload);

        if (file_put_contents($outputPath, $imageData) === false) {
            throw new RuntimeException("Failed to write PNG QR code to: $outputPath");
        }
    }

    /**
     * Renders the payment QR code as an SVG file.
     */
    public static function svg(Payment $payment, string $outputPath): void
    {
        $payload = Encoder::encode($payment);

        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'eccLevel' => EccLevel::L,
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
        ]);

        $qrcode = new QRCode($options);
        $svgData = $qrcode->render($payload);

        if (file_put_contents($outputPath, $svgData) === false) {
            throw new RuntimeException("Failed to write SVG QR code to: $outputPath");
        }
    }
}
