<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hrmpfz\PayBySquare\Payment;
use Hrmpfz\PayBySquare\PayBySquare;

$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));

if ($isCli) {
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
    $message = get_input("Enter Message/Note", "registracny poplatok");
    $date = get_input("Enter Due Date (YYYY-MM-DD)", "2026-07-13");

    run_verification($iban, $bic, $amount, $vs, $message, $date);
} else {
    // Web Mode
    $iban = trim($_GET['iban'] ?? 'SK0000000000000000000000');
    $bic = trim($_GET['bic'] ?? 'BANKSKBX');
    $amount = trim($_GET['amount'] ?? '49.00');
    $vs = trim($_GET['vs'] ?? '202600123');
    $message = trim($_GET['message'] ?? 'registracny poplatok');
    $date = trim($_GET['date'] ?? '2026-07-13');

    $payload = null;
    $error = null;
    $verificationResult = null;

    if (isset($_GET['submit'])) {
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

            if (
                $iban === "SK0000000000000000000000" &&
                $bic === "BANKSKBX" &&
                $amount === "49.00" &&
                $vs === "202600123" &&
                $message === "registracny poplatok" &&
                $date === "2026-07-13"
            ) {
                $expected = "0806A0006GD1KBO092Q7FAVOUC0SKTSPSCECNQ1NFN3J9GEVFOP9O4A61QR0LV8BSD38GRVLG6BQ1HPEHNSCUR59HET1HNG50EQ29GQNAHO3Q77S8MR63KH37206QIIBRPQQE9SNBSEKTPQGO27O93IF5ELNG";
                if ($payload === $expected) {
                    $verificationResult = "✅ VERIFICATION SUCCESS: Output matches the standard JS reference exactly!";
                } else {
                    $verificationResult = "❌ VERIFICATION FAILED:<br>Expected: $expected<br>Got: $payload";
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="sk">
    <head>
        <meta charset="UTF-8">
        <title>PAY by square Verification</title>
        <style>
            body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; line-height: 1.6; }
            .form-group { margin-bottom: 15px; }
            label { display: block; font-weight: bold; margin-bottom: 5px; }
            input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; }
            button { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
            .result { background: #f4f4f4; padding: 15px; border-radius: 5px; margin-top: 20px; word-break: break-all; }
            .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px; }
            .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <h1>PAY by square Verification</h1>
        <p>Tento skript slúži na overenie správnosti vygenerovaného platobného reťazca.</p>
        <form method="GET" action="">
            <div class="form-group">
                <label for="iban">IBAN</label>
                <input type="text" id="iban" name="iban" value="<?php echo htmlspecialchars($iban); ?>">
            </div>
            <div class="form-group">
                <label for="bic">BIC</label>
                <input type="text" id="bic" name="bic" value="<?php echo htmlspecialchars($bic); ?>">
            </div>
            <div class="form-group">
                <label for="amount">Suma (EUR)</label>
                <input type="text" id="amount" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            </div>
            <div class="form-group">
                <label for="vs">Variabilný symbol</label>
                <input type="text" id="vs" name="vs" value="<?php echo htmlspecialchars($vs); ?>">
            </div>
            <div class="form-group">
                <label for="message">Správa pre príjemcu</label>
                <input type="text" id="message" name="message" value="<?php echo htmlspecialchars($message); ?>">
            </div>
            <div class="form-group">
                <label for="date">Dátum splatnosti (YYYY-MM-DD)</label>
                <input type="text" id="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
            </div>
            <button type="submit" name="submit" value="1">Overiť a vygenerovať</button>
        </form>

        <?php if ($error): ?>
            <div class="error">
                <strong>Chyba:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($payload): ?>
            <div class="result">
                <h3>Vygenerovaný reťazec:</h3>
                <code><?php echo htmlspecialchars($payload); ?></code>
            </div>
            <?php if ($verificationResult): ?>
                <div class="<?php echo strpos($verificationResult, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $verificationResult; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

function run_verification($iban, $bic, $amount, $vs, $message, $date) {
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

        if (
            $iban === "SK0000000000000000000000" &&
            $bic === "BANKSKBX" &&
            $amount === "49.00" &&
            $vs === "202600123" &&
            $message === "registracny poplatok" &&
            $date === "2026-07-13"
        ) {
            $expected = "0806A0006GD1KBO092Q7FAVOUC0SKTSPSCECNQ1NFN3J9GEVFOP9O4A61QR0LV8BSD38GRVLG6BQ1HPEHNSCUR59HET1HNG50EQ29GQNAHO3Q77S8MR63KH37206QIIBRPQQE9SNBSEKTPQGO27O93IF5ELNG";
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
}
