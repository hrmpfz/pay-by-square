<?php

namespace Hrmpfz\PayBySquare;

use Hrmpfz\PayBySquare\Qr\Renderer;

/**
 * Public facade API for the PAY by square library.
 */
class PayBySquare
{
    /**
     * Generate the Base32Hex encoded PAY by square payload.
     *
     * @param Payment $payment
     * @return string
     */
    public static function encode(Payment $payment): string
    {
        return Encoder::encode($payment);
    }

    /**
     * Generate a PNG QR code for the given payment.
     * Requires the GD extension.
     *
     * @param Payment $payment
     * @param string $outputPath
     * @return void
     */
    public static function png(Payment $payment, string $outputPath): void
    {
        Renderer::png($payment, $outputPath);
    }

    /**
     * Generate an SVG QR code for the given payment.
     *
     * @param Payment $payment
     * @param string $outputPath
     * @return void
     */
    public static function svg(Payment $payment, string $outputPath): void
    {
        Renderer::svg($payment, $outputPath);
    }
}
