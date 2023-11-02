<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle;

use DateTimeImmutable;
use Digitick\Sepa\TransferFile\Facade\CustomerDirectDebitFacade;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Lens\Bundle\LensSepaBundle\Data\DirectDebitTransactionInformation;
use Lens\Bundle\LensSepaBundle\Data\PaymentInformation;
use Lens\Bundle\LensSepaBundle\Exception\InvalidArgument;

class Generator
{
    private readonly DateTimeImmutable $requestedCollectionDate;

    public function __construct(
        string $requestedCollectionDate,
        private readonly string $creditorId,
        private readonly string $creditorName,
        private readonly string $creditorIban,
    ) {
        $this->requestedCollectionDate = new DateTimeImmutable($requestedCollectionDate);
    }

    public function generate(PaymentInformation $paymentInformation): CustomerDirectDebitFacade
    {
        $creditorId = $paymentInformation->creditorId ??= $this->creditorId;
        $creditorName = $paymentInformation->creditorName ??= $this->creditorName;
        $creditorIban = $paymentInformation->creditorIban ??= $this->creditorIban;
        if (!$creditorId || !$creditorName || !$creditorIban) {
            throw new InvalidArgument('One or more creditor payment information options are missing from config & argument.');
        }

        $directDebit = $this->createDirectDebit($paymentInformation);
        $this->addCreditorData($paymentInformation, $directDebit);

        foreach ($paymentInformation->transfers as $transfer) {
            $this->addTransfer($directDebit, $transfer);
        }

        return $directDebit;
    }

    private function createDirectDebit(PaymentInformation $paymentInformation): CustomerDirectDebitFacade
    {
        return TransferFileFacadeFactory::createDirectDebit(
            $paymentInformation->id,
            $paymentInformation->creditorName,
            'pain.008.001.02',
        );
    }

    private function addCreditorData(
        PaymentInformation $paymentInformation,
        CustomerDirectDebitFacade $directDebit,
    ): void {
        $directDebit->addPaymentInfo($paymentInformation->id, [
            'id' => sprintf('%s-%s', $paymentInformation->id, $paymentInformation->sequenceType),
            'dueDate' => $this->requestedCollectionDate($paymentInformation)->format('Y-m-d'),
            'creditorId' => $paymentInformation->creditorId,
            'creditorName' => $paymentInformation->creditorName,
            'creditorAccountIBAN' => $paymentInformation->creditorIban,
            'seqType' => $paymentInformation->sequenceType,
        ]);
    }

    private function addTransfer(
        CustomerDirectDebitFacade $directDebit,
        DirectDebitTransactionInformation $directDebitTransaction,
    ): void {
        $reference = $directDebitTransaction->paymentId;

        $directDebit->addTransfer($reference, [
            'endToEndId' => $reference,
            'amount' => $directDebitTransaction->instructedAmount->getMinorAmount()->toInt(),
            'debtorIban' => $directDebitTransaction->iban,
            'debtorName' => iconv('UTF-8', 'US-ASCII//TRANSLIT', $directDebitTransaction->name),
            'debtorMandate' => $reference,
            'debtorMandateSignDate' => date('Y-m-d'),
            'remittanceInformation' => iconv('UTF-8', 'US-ASCII//TRANSLIT', $directDebitTransaction->remittanceInformation),
        ]);
    }

    private function requestedCollectionDate(PaymentInformation $paymentInformation): DateTimeImmutable
    {
        static $requestedCollectionDate;
        if (!isset($requestedCollectionDate)) {
            $requestedCollectionDate = DateTimeImmutable::createFromInterface(
                $paymentInformation->requestedCollectionDate ?? $this->requestedCollectionDate
            );
        }

        return $requestedCollectionDate;
    }
}
