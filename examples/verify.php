<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hrmpfz\PayBySquare\Payment;
use Hrmpfz\PayBySquare\PayBySquare;

echo "=== PAY by square Verification ===\n";
echo "This script allows you to input payment details to generate a PAY by square payload.\n";
echo "If default values are kept, it will check the output against the reference implementation output.\n\n";

// Helper to read inputs with defaults
function get_input(string $prompt, string $default): string {
    echo $prompt . " [default: " . $default . "]: ";
    $input = trim(fgets(STDIN));
    return $input === "" ? $default : $input;
}

$iban = get_input("Enter IBAN", "SK0000000000000000000000");
$bic = get_input("Enter BIC", "BANKSKBX");
$amount = get_input("Enter Amount", "49.00");
$vs = get_input("Enter Variable Symbol", "202600123");
$message = get_input("Enter Message/Note", "Stvrtok o siestej - registracia");
$date = get_input("Enter Due Date (YYYY-MM-DD)", "2026-07-13");

try {
    $payment = Payment::create()
        ->iban($iban)
        ->bic($bic)
        ->amount($amount)
        ->currency('EUR')
        ->variableSymbol($vs)
        ->message($message)
        ->dueDate(new DateTimeImmutable($date));

    $payload = PayBySquare::encode($payment);
    echo "\nGenerated PAY by square string:\n";
    echo $payload . "\n\n";

    // If using the default test vector, verify byte-by-byte
    if (
        $iban === "SK0000000000000000000000" &&
        $bic === "BANKSKBX" &&
        $amount === "49.00" &&
        $vs === "202600123" &&
        $message === "Stvrtok o siestej - registracia" &&
        $date === "2026-07-13"
    ) {
        // Output from standard JS bysquare package using node's test_vector.js
        $expected = "08070000D4E9N0EG9319N3I09Q1KQBVD6FA4302GPAREQJFPCELV83U6NGT1H30Q7ECOLAIOCIS55RACHH8DCK21HBQMC0QO0EF88PFRPAUCUC4L8O3E62MC4AI0KOIALRJAVLOALBA69CMV0D9VVVIDCU00";
        if ($payload === $expected) {
            echo "✅ VERIFICATION SUCCESS: Output matches the standard JS reference exactly!\n";
        } else {
            echo "❌ VERIFICATION FAILED:\n";
            echo "Expected: " . $expected . "\n";
            echo "Got:      " . $payload . "\n";
        }
    } else {
        echo "To decode and verify this payload, you can use the node-based 'bysquare' CLI tool:\n";
        echo "  npx bysquare decode " . $payload . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
