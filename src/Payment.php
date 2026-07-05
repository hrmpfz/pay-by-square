<?php

namespace Hrmpfz\PayBySquare;

use DateTimeInterface;

/**
 * Fluent Payment Data Model for PAY by square.
 */
class Payment
{
    private ?string $invoiceId = null;
    private int $type = 1; // 1 = PaymentOrder, 2 = StandingOrder, 4 = DirectDebit
    private ?string $amount = null;
    private string $currency = 'EUR';
    private ?string $dueDate = null;
    private ?string $variableSymbol = null;
    private ?string $constantSymbol = null;
    private ?string $specificSymbol = null;
    private ?string $originatorsReferenceInformation = null;
    private ?string $paymentNote = null;
    
    /** @var array<array{iban: string, bic: ?string}> */
    private array $bankAccounts = [];

    // Beneficiary
    private ?string $beneficiaryName = null;
    private ?string $beneficiaryStreet = null;
    private ?string $beneficiaryCity = null;

    public static function create(): self
    {
        return new self();
    }

    public function invoiceId(?string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    public function type(int $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function amount(?string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function currency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Set payment due date.
     *
     * @param DateTimeInterface|string|null $dueDate DateTime object or YYYYMMDD string.
     */
    public function dueDate($dueDate): self
    {
        if ($dueDate instanceof DateTimeInterface) {
            $this->dueDate = $dueDate->format('Ymd');
        } else {
            $this->dueDate = $dueDate;
        }
        return $this;
    }

    public function variableSymbol(?string $variableSymbol): self
    {
        $this->variableSymbol = $variableSymbol;
        return $this;
    }

    public function constantSymbol(?string $constantSymbol): self
    {
        $this->constantSymbol = $constantSymbol;
        return $this;
    }

    public function specificSymbol(?string $specificSymbol): self
    {
        $this->specificSymbol = $specificSymbol;
        return $this;
    }

    public function originatorsReferenceInformation(?string $reference): self
    {
        $this->originatorsReferenceInformation = $reference;
        return $this;
    }

    public function reference(?string $reference): self
    {
        return $this->originatorsReferenceInformation($reference);
    }

    public function paymentNote(?string $paymentNote): self
    {
        $this->paymentNote = $paymentNote;
        return $this;
    }

    public function message(?string $message): self
    {
        return $this->paymentNote($message);
    }

    public function iban(string $iban): self
    {
        $cleanIban = str_replace(' ', '', $iban);
        if (empty($this->bankAccounts)) {
            $this->bankAccounts[] = ['iban' => $cleanIban, 'bic' => null];
        } else {
            $this->bankAccounts[0]['iban'] = $cleanIban;
        }
        return $this;
    }

    public function bic(?string $bic): self
    {
        $cleanBic = $bic !== null ? str_replace(' ', '', $bic) : null;
        if (empty($this->bankAccounts)) {
            $this->bankAccounts[] = ['iban' => '', 'bic' => $cleanBic];
        } else {
            $this->bankAccounts[0]['bic'] = $cleanBic;
        }
        return $this;
    }

    public function beneficiaryName(?string $name): self
    {
        $this->beneficiaryName = $name;
        return $this;
    }

    public function beneficiaryStreet(?string $street): self
    {
        $this->beneficiaryStreet = $street;
        return $this;
    }

    public function beneficiaryCity(?string $city): self
    {
        $this->beneficiaryCity = $city;
        return $this;
    }

    // Getters for serialization
    public function getInvoiceId(): ?string { return $this->invoiceId; }
    public function getType(): int { return $this->type; }
    public function getAmount(): ?string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function getVariableSymbol(): ?string { return $this->variableSymbol; }
    public function getConstantSymbol(): ?string { return $this->constantSymbol; }
    public function getSpecificSymbol(): ?string { return $this->specificSymbol; }
    public function getOriginatorsReferenceInformation(): ?string { return $this->originatorsReferenceInformation; }
    public function getPaymentNote(): ?string { return $this->paymentNote; }
    public function getBankAccounts(): array { return $this->bankAccounts; }
    public function getBeneficiaryName(): ?string { return $this->beneficiaryName; }
    public function getBeneficiaryStreet(): ?string { return $this->beneficiaryStreet; }
    public function getBeneficiaryCity(): ?string { return $this->beneficiaryCity; }
}
