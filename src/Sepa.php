<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle;

use Lens\Bundle\LensSepaBundle\Adapter\AdapterInterface;
use Lens\Bundle\LensSepaBundle\Data\CustomerDirectDebitInitiation;
use Lens\Bundle\LensSepaBundle\Data\PaymentInformation;

class Sepa implements AdapterInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {
    }

    public function all(): iterable
    {
        return $this->adapter->all();
    }

    public function has(string $id): bool
    {
        return $this->adapter->has($id);
    }

    public function load(string $id): CustomerDirectDebitInitiation
    {
        return $this->adapter->load($id);
    }

    public function save(PaymentInformation $paymentInformation): CustomerDirectDebitInitiation
    {
        return $this->adapter->save($paymentInformation);
    }

    public function remove(string $id): void
    {
        $this->adapter->remove($id);
    }
}
