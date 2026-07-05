<?php

namespace Hrmpfz\PayBySquare\Tests;

use PHPUnit\Framework\TestCase;
use Hrmpfz\PayBySquare\Payment;
use Hrmpfz\PayBySquare\PayBySquare;

class PayBySquareTest extends TestCase
{
    /**
     * Test the default test vector against the output of the standard JS bysquare package.
     */
    public function testDefaultTestVectorMatchesReference(): void
    {
        $payment = Payment::create()
            ->iban('SK0000000000000000000000')
            ->bic('BANKSKBX')
            ->amount('49.00')
            ->currency('EUR')
            ->variableSymbol('202600123')
            ->message('Stvrtok o siestej - registracia')
            ->dueDate(new \DateTimeImmutable('2026-07-13'));

        $payload = PayBySquare::encode($payment);
        
        $expected = "08070000D4E9N0EG9319N3I09Q1KQBVD6FA4302GPAREQJFPCELV83U6NGT1H30Q7ECOLAIOCIS55RACHH8DCK21HBQMC0QO0EF88PFRPAUCUC4L8O3E62MC4AI0KOIALRJAVLOALBA69CMV0D9VVVIDCU00";
        
        $this->assertEquals($expected, $payload);
    }

    /**
     * Test that SVG file is successfully generated.
     */
    public function testQrRenderingSvg(): void
    {
        $payment = Payment::create()
            ->iban('SK0000000000000000000000')
            ->amount('10.00');

        $outputPath = sys_get_temp_dir() . '/test_payment.svg';
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        PayBySquare::svg($payment, $outputPath);

        $this->assertFileExists($outputPath);
        $svgContent = file_get_contents($outputPath);
        $this->assertStringContainsString('<svg', $svgContent);
        
        unlink($outputPath);
    }

    /**
     * Test that PNG file is successfully generated (only if GD is loaded).
     */
    public function testQrRenderingPng(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not loaded.');
        }

        $payment = Payment::create()
            ->iban('SK0000000000000000000000')
            ->amount('10.00');

        $outputPath = sys_get_temp_dir() . '/test_payment.png';
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        PayBySquare::png($payment, $outputPath);

        $this->assertFileExists($outputPath);
        
        unlink($outputPath);
    }
}
