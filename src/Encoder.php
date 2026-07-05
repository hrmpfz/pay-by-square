<?php

namespace Hrmpfz\PayBySquare;

use Hrmpfz\PayBySquare\Lzma\RangeEncoder;
use InvalidArgumentException;

/**
 * Handles serialization, checksumming, compression, and framing
 * of the PAY by square payment model.
 */
class Encoder
{
    /**
     * Deburrs string by converting Slovak and common European diacritics
     * to basic Latin letters.
     */
    public static function deburr(string $text): string
    {
        static $map = [
            'Á' => 'A', 'Ä' => 'A', 'Č' => 'C', 'Ď' => 'D', 'É' => 'E', 'Í' => 'I',
            'Ľ' => 'L', 'Ĺ' => 'L', 'Ň' => 'N', 'Ó' => 'O', 'Ô' => 'O', 'Ŕ' => 'R',
            'Š' => 'S', 'Ť' => 'T', 'Ú' => 'U', 'Ý' => 'Y', 'Ž' => 'Z',
            'á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'í' => 'i',
            'ľ' => 'l', 'ĺ' => 'l', 'ň' => 'n', 'ó' => 'o', 'ô' => 'o', 'ŕ' => 'r',
            'š' => 's', 'ť' => 't', 'ú' => 'u', 'ý' => 'y', 'ž' => 'z',
            'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
            'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'Ç' => 'C', 'ç' => 'c',
            'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ñ' => 'N', 'ñ' => 'n',
            'Ò' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'ò' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ý' => 'Y', 'ý' => 'y', 'ÿ' => 'y',
            'Æ' => 'Ae', 'æ' => 'ae',
            'ß' => 'ss',
        ];

        return strtr($text, $map);
    }

    /**
     * Replaces tab characters with spaces.
     */
    public static function sanitize(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return str_replace("\t", " ", $value);
    }

    /**
     * Serializes Payment model to the standard horizontal tab-separated format.
     */
    public static function serialize(Payment $payment): string
    {
        $fields = [];

        // Base fields
        $fields[] = self::sanitize($payment->getInvoiceId());
        $fields[] = '1'; // paymentsCount (currently supporting 1 payment)

        // Payment fields
        $fields[] = (string)$payment->getType();
        
        // Format amount: strip trailing .00 or format as string decimal
        $amount = $payment->getAmount();
        if ($amount !== null) {
            $amount = (string)$amount;
            if (str_contains($amount, '.')) {
                $amount = rtrim(rtrim($amount, '0'), '.');
            }
        }
        $fields[] = self::sanitize($amount);
        
        $fields[] = self::sanitize($payment->getCurrency());
        $fields[] = self::sanitize($payment->getDueDate());
        $fields[] = self::sanitize($payment->getVariableSymbol());
        $fields[] = self::sanitize($payment->getConstantSymbol());
        $fields[] = self::sanitize($payment->getSpecificSymbol());
        $fields[] = self::sanitize($payment->getOriginatorsReferenceInformation());
        
        // paymentNote / message is deburred to ensure support in older/strict banking apps
        $fields[] = self::sanitize(self::deburr($payment->getPaymentNote() ?? ''));

        // Bank accounts
        $accounts = $payment->getBankAccounts();
        if (empty($accounts)) {
            throw new InvalidArgumentException("Payment must contain at least one bank account (IBAN).");
        }
        $fields[] = (string)count($accounts);
        foreach ($accounts as $acc) {
            $fields[] = self::sanitize($acc['iban']);
            $fields[] = self::sanitize($acc['bic']);
        }

        // Standing order extension (disabled -> "0")
        $fields[] = "0";

        // Direct debit extension (disabled -> "0")
        $fields[] = "0";

        // Beneficiary fields (deburred)
        $fields[] = self::sanitize(self::deburr($payment->getBeneficiaryName() ?? ''));
        $fields[] = self::sanitize(self::deburr($payment->getBeneficiaryStreet() ?? ''));
        $fields[] = self::sanitize(self::deburr($payment->getBeneficiaryCity() ?? ''));

        return implode("\t", $fields);
    }

    /**
     * Encodes a Payment object into a standard Base32Hex encoded BySquare string.
     */
    public static function encode(Payment $payment): string
    {
        // 1. Serialize
        $serialized = self::serialize($payment);

        // 2. Compute CRC32 and prepend (4 bytes, little-endian)
        $checksum = crc32($serialized) & 0xFFFFFFFF;
        $checksumBytes = pack('V', $checksum);
        $payloadChecked = $checksumBytes . $serialized;

        // 3. Compress using pure-PHP LZMA range encoder
        $compressedBody = RangeEncoder::compress($payloadChecked);

        // 4. Construct BySquare frame
        // BySquare Header (2 bytes): Type=0 (PAY), Version=2 (1.2.0), DocType=0, Reserved=0 -> [0x02, 0x00]
        $headerBytes = pack('C*', 0x02, 0x00);
        // Payload Length (2 bytes, little-endian)
        $lengthBytes = pack('v', strlen($payloadChecked));

        $binaryPackage = $headerBytes . $lengthBytes . $compressedBody;

        // 5. Encode using Base32Hex
        return self::encodeBase32Hex($binaryPackage);
    }

    /**
     * Encodes binary data using RFC 4648 Base32Hex without padding.
     */
    public static function encodeBase32Hex(string $input): string
    {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUV";
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $buffer = (($buffer << 8) | ord($input[$i])) & 0xFFFFFFFFFF;
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $index = ($buffer >> $bitsLeft) & 0x1F;
                $output .= $chars[$index];
            }
        }

        if ($bitsLeft > 0) {
            $index = ($buffer << (5 - $bitsLeft)) & 0x1F;
            $output .= $chars[$index];
        }

        return $output;
    }
}
