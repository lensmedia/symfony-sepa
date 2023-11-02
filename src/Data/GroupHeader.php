<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Data;

use DateTimeImmutable;

class GroupHeader
{
    public string $messageId;

    public DateTimeImmutable $creationDateAndTime;

    public int $numberOfTransactions;

    public PaymentInformation $paymentInformation;
}
