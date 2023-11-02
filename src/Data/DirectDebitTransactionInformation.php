<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Data;

use Brick\Money\Money;
use Iban\Validation\Iban;
use Iban\Validation\Validator;
use Lens\Bundle\LensSepaBundle\Exception\InvalidArgument;

class DirectDebitTransactionInformation
{
    public string $paymentId;

    public Money $instructedAmount;

    public string $name;

    public string $iban;

    public string $remittanceInformation;

    public static function create(
        Money $instructedAmount,
        string $name,
        string $iban,
        ?string $remittanceInformation = null,
    ): self {
        $validator = new Validator();
        $iban = new Iban($iban);

        if (!$validator->validate($iban)) {
            throw new InvalidArgument('Invalid IBAN for debtor: '.implode(
                ' ',
                $validator->getViolations(),
            ));
        }

        $instance = new self();
        $instance->instructedAmount = $instructedAmount;
        $instance->name = $name;
        $instance->iban = $iban->format(Iban::FORMAT_ELECTRONIC);
        $instance->remittanceInformation = $remittanceInformation ?? $paymentId;

        return $instance;
    }
}
