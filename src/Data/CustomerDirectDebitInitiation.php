<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Data;

class CustomerDirectDebitInitiation
{
    public string $file;

    public string $version;

    public GroupHeader $groupHeader;

    public PaymentInformation $paymentInformation;
}
