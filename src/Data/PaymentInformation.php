<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Data;

use Brick\Money\Money;
use DateTimeImmutable;
use Digitick\Sepa\PaymentInformation as PI;

class PaymentInformation
{
    public const SEQUENCE_TYPE_FINAL = PI::S_FINAL;
    public const SEQUENCE_TYPE_FIRST = PI::S_FIRST;
    public const SEQUENCE_TYPE_ONEOFF = PI::S_ONEOFF;
    public const SEQUENCE_TYPE_RECURRING = PI::S_RECURRING;

    public string $id;

    public string $sequenceType = self::SEQUENCE_TYPE_ONEOFF;

    public ?DateTimeImmutable $requestedCollectionDate = null;

    public ?string $creditorId = null;

    public ?string $creditorName = null;

    public ?string $creditorIban = null;

    /** @var DirectDebitTransactionInformation[] */
    public array $transfers = [];

    public static function create(
        string $id,
        ?string $creditorId = null,
        ?string $creditorName = null,
        ?string $creditorIban = null,
        ?DateTimeImmutable $requestedCollectionDate = null,
        string $sequenceType = self::SEQUENCE_TYPE_ONEOFF,
        array $transfers = [],
    ): static {
        $instance = new self();
        $instance->id = $id;
        $instance->creditorId = $creditorId;
        $instance->creditorName = $creditorName;
        $instance->creditorIban = $creditorIban;
        $instance->requestedCollectionDate = $requestedCollectionDate;
        $instance->sequenceType = $sequenceType;

        foreach ($transfers as $transfer) {
            $instance->addTransfer($transfer);
        }

        return $instance;
    }

    public function addTransfer(DirectDebitTransactionInformation $transfer): void
    {
        if (!isset($transfer->paymentId)) {
            $transfer->paymentId = $this->id;
        }

        $this->transfers[] = $transfer;
    }

    /**
     * This field is not always available, but we can calculate it nonetheless.
     */
    public function controlSum(): Money
    {
        $total = Money::zero('EUR');
        foreach ($this->transfers as $transfer) {
            $total = $total->plus($transfer->instructedAmount);
        }

        return $total;
    }
}
