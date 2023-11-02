<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Adapter;

use Lens\Bundle\LensSepaBundle\Data\CustomerDirectDebitInitiation;
use Lens\Bundle\LensSepaBundle\Data\PaymentInformation;
use Lens\Bundle\LensSepaBundle\Exception\DuplicateId;
use Lens\Bundle\LensSepaBundle\Exception\IdNotFound;

interface AdapterInterface
{
    /** @return array with all IDs */
    public function all(): iterable;

    public function has(string $id): bool;

    /**
     * @throws IdNotFound
     */
    public function load(string $id): CustomerDirectDebitInitiation;

    /**
     * @throws DuplicateId
     */
    public function save(PaymentInformation $paymentInformation): CustomerDirectDebitInitiation;

    public function remove(string $id): void;
}
