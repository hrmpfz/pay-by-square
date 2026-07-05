<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hrmpfz\PayBySquare\Payment;
use Hrmpfz\PayBySquare\PayBySquare;

$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));

try {
    // 1. Create a payment object with details
    $payment = Payment::create()
        ->iban('SK0000000000000000000000')
        ->bic('BANKSKBX')
        ->amount('49.00')
        ->currency('EUR')
        ->variableSymbol('202600123')
        ->message('registracny poplatok')
        ->dueDate(new DateTimeImmutable('2026-07-13'));

    // 2. Generate the PAY by square string
    $payload = PayBySquare::encode($payment);

    if ($isCli) {
        echo "PAY by square string:\n";
        echo $payload . "\n\n";

        // 3. Render PNG QR Code (requires GD extension)
        if (extension_loaded('gd')) {
            PayBySquare::png($payment, __DIR__ . '/payment.png');
            echo "Successfully generated PNG QR code: " . __DIR__ . "/payment.png\n";
        } else {
            echo "Skipping PNG generation: PHP GD extension not loaded.\n";
        }

        // 4. Render SVG QR Code
        PayBySquare::svg($payment, __DIR__ . '/payment.svg');
        echo "Successfully generated SVG QR code: " . __DIR__ . "/payment.svg\n";
    } else {
        // Web Mode - Render inline
        $qrCodePng = null;
        if (extension_loaded('gd')) {
            $tempPng = tempnam(sys_get_temp_dir(), 'pbs_');
            PayBySquare::png($payment, $tempPng);
            $qrCodePng = base64_encode(file_get_contents($tempPng));
            unlink($tempPng);
        }

        $tempSvg = tempnam(sys_get_temp_dir(), 'pbs_');
        PayBySquare::svg($payment, $tempSvg);
        $qrCodeSvg = file_get_contents($tempSvg);
        unlink($tempSvg);
        ?>
        <!DOCTYPE html>
        <html lang="sk">
        <head>
            <meta charset="UTF-8">
            <title>PAY by square Example Generator</title>
            <style>
                body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; line-height: 1.6; }
                .qr-box { display: flex; gap: 20px; margin-top: 20px; }
                .qr-item { flex: 1; border: 1px solid #ccc; padding: 15px; text-align: center; border-radius: 5px; background: #fff; }
                pre { background: #f4f4f4; padding: 15px; word-break: break-all; white-space: pre-wrap; border-radius: 5px; }
            </style>
        </head>
        <body>
            <h1>PAY by square Example Generator</h1>
            <p>Tento príklad vygeneroval nasledujúci platobný reťazec:</p>
            <pre><code><?php echo htmlspecialchars($payload); ?></code></pre>

            <div class="qr-box">
                <?php if ($qrCodePng): ?>
                    <div class="qr-item">
                        <h3>PNG QR kód</h3>
                        <img src="data:image/png;base64,<?php echo $qrCodePng; ?>" alt="PNG QR" style="max-width: 200px; width: 100%; height: auto;">
                    </div>
                <?php endif; ?>
                <div class="qr-item">
                    <h3>SVG QR kód</h3>
                    <div style="max-width: 200px; margin: 0 auto;"><?php echo $qrCodeSvg; ?></div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
