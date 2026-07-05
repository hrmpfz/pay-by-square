<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hrmpfz\PayBySquare\Payment;
use Hrmpfz\PayBySquare\PayBySquare;

try {
    // 1. Create a payment object with details
    $payment = Payment::create()
        ->iban('SK0000000000000000000000')
        ->bic('BANKSKBX')
        ->amount('49.00')
        ->currency('EUR')
        ->variableSymbol('202600123')
        ->message('Stvrtok o siestej - registracia')
        ->dueDate(new DateTimeImmutable('2026-07-13'));

    // 2. Generate the PAY by square string
    $payload = PayBySquare::encode($payment);
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

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
