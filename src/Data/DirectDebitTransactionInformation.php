<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Data;

use Brick\Money\Money;
use DateTimeImmutable;
use Iban\Validation\Iban;
use Iban\Validation\Validator;
use Lens\Bundle\LensSepaBundle\Exception\InvalidArgument;

class DirectDebitTransactionInformation
{
    /**
     * @var string End-to-End Reference number of the direct debit, this information is sent to the debtor (alphanumeric, max 35)
     */
    public string $endToEndId;

    /**
     * @var string Mandate ID, this information is sent to the debtor (alphanumeric, max 35, defaults to endToEndId)
     */
    public string $mandateId;

    public DateTimeImmutable $mandateDate;

    public Money $instructedAmount;

    public string $name;

    public string $iban;

    /**
     * @var string Remittance information, this information is sent to the debtor (alphanumeric, max 140)
     */
    public string $remittanceInformation;

    public static function create(
        string $endToEndId,
        Money $instructedAmount,
        string $name,
        string $iban,
        string $remittanceInformation,
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
        $instance->endToEndId = $endToEndId;
        $instance->setMandate($endToEndId);

        $instance->name = $name;
        $instance->iban = $iban->format(Iban::FORMAT_ELECTRONIC);
        $instance->instructedAmount = $instructedAmount;
        $instance->remittanceInformation = $remittanceInformation;

        return $instance;
    }

    /**
     * Set our custom mandate values. If not manually called mandateId defaults to EndToEndId.
     *
     * @param ?DateTimeImmutable $mandateDate the date the mandate was signed (defaults to "now")
     */
    public function setMandate(string $mandateId, ?DateTimeImmutable $mandateDate = null): void
    {
        $this->mandateId = $mandateId;
        $this->mandateDate = $mandateDate ?? new DateTimeImmutable();
    }
}
