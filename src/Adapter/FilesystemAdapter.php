<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Adapter;

use Brick\Money\Money;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Lens\Bundle\LensSepaBundle\Data\CustomerDirectDebitInitiation;
use Lens\Bundle\LensSepaBundle\Data\DirectDebitTransactionInformation;
use Lens\Bundle\LensSepaBundle\Data\GroupHeader;
use Lens\Bundle\LensSepaBundle\Data\PaymentInformation;
use Lens\Bundle\LensSepaBundle\Exception\DuplicateId;
use Lens\Bundle\LensSepaBundle\Exception\IdNotFound;
use Lens\Bundle\LensSepaBundle\Exception\InvalidArgument;
use Lens\Bundle\LensSepaBundle\Exception\MissingGroupHeader;
use Lens\Bundle\LensSepaBundle\Exception\MissingPaymentInformation;
use Lens\Bundle\LensSepaBundle\Exception\UnsupportedVersion;
use Lens\Bundle\LensSepaBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

use function in_array;

use const PATHINFO_FILENAME;

class FilesystemAdapter implements AdapterInterface
{
    private const PAIN_008_001_02 = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02';

    private const SUPPORT_VERSIONS = [
        self::PAIN_008_001_02,
    ];

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Generator $generator,
        private readonly string $savePath,
        private readonly bool $isDebug,
    ) {
    }

    public function all(): iterable
    {
        return array_map(
            static fn (string $file) => pathinfo($file, PATHINFO_FILENAME),
            glob($this->path().'/*.xml'),
        );
    }

    public function has(string $id): bool
    {
        return file_exists($this->file($id));
    }

    public function load(string $id): CustomerDirectDebitInitiation
    {
        if (!$this->has($id)) {
            throw new IdNotFound(sprintf(
                'A direct debit batch with the ID "%s" does not exist.',
                $id,
            ));
        }

        return $this->parse($this->file($id));
    }

    public function save(PaymentInformation $paymentInformation, bool $replace = false): CustomerDirectDebitInitiation
    {
        if (!$replace && $this->has($paymentInformation->id)) {
            throw new DuplicateId(sprintf(
                'A direct debit batch with the ID "%s" already exists.',
                $paymentInformation->id,
            ));
        }

        $path = $this->file($paymentInformation->id);

        $this->filesystem->dumpFile(
            $path,
            $this->generator->generate($paymentInformation)->asXML(),
        );

        return $this->load($paymentInformation->id);
    }

    public function remove(string $id): void
    {
        if (!$this->has($id)) {
            throw new IdNotFound(sprintf(
                'A direct debit batch with the ID "%s" does not exist.',
                $id,
            ));
        }

        $this->filesystem->remove($this->file($id));
    }

    private function file(string $id): string
    {
        return sprintf('%s/%s.xml', $this->path(), $id);
    }

    private function path(): string
    {
        return $this->savePath;
    }

    private function parse(string $path): CustomerDirectDebitInitiation
    {
        static $parsed = [];
        if (isset($parsed[$path])) {
            return $parsed[$path];
        }

        $document = $this->initializeDocument($path);
        if ($this->isDebug) {
            $document->preserveWhiteSpace = false;
            $document->formatOutput = true;
        }

        $this->validateVersion($document);

        $customerDirectDebitInitiation = $this->customerDirectDebitInitiation($document);
        $customerDirectDebitInitiation->file = $path;

        $customerDirectDebitInitiation->groupHeader
            = $groupHeader
            = $this->groupHeader($document);

        $customerDirectDebitInitiation->paymentInformation
            = $paymentInformation
            = $this->paymentInformation($document);

        $paymentInformation->transfers
            = $transfers
            = $this->transfers($document);

        return $parsed[$path] = $customerDirectDebitInitiation;
    }

    private function initializeDocument(File|string $path): DOMDocument
    {
        $path = (string)$path;
        if (!file_exists($path)) {
            throw new InvalidArgument(sprintf(
                'File "%s" does not exist.',
                $path,
            ));
        }

        $doc = new DOMDocument();
        $doc->load($path);

        return $doc;
    }

    private function validateVersion(DOMDocument $document): void
    {
        $version = $this->version($document);
        if (!in_array($version, self::SUPPORT_VERSIONS, true)) {
            throw new UnsupportedVersion(sprintf(
                'Unsupported version %s in %s',
                $version,
                $document->baseURI,
            ));
        }
    }

    private function version(DOMDocument $doc): string
    {
        return $doc->documentElement->getAttribute('xmlns');
    }

    private function customerDirectDebitInitiation(DOMDocument $document): CustomerDirectDebitInitiation
    {
        $customerDirectDebitInitiation = new CustomerDirectDebitInitiation();
        $customerDirectDebitInitiation->version = $this->version($document);

        return $customerDirectDebitInitiation;
    }

    private function groupHeader(DOMDocument $document): GroupHeader
    {
        $xpath = self::xpath($document);

        $groupHeaderNode = match ($this->version($document)) {
            self::PAIN_008_001_02 => $xpath->query('xmlns:CstmrDrctDbtInitn/xmlns:GrpHdr')[0] ?? null,
        };

        if (!$groupHeaderNode) {
            throw new MissingGroupHeader(sprintf(
                'Missing group header in "%s"',
                $document->baseURI,
            ));
        }

        $groupHeader = new GroupHeader();
        $groupHeader->messageId = $xpath->query('xmlns:MsgId', $groupHeaderNode)[0]->nodeValue;
        $groupHeader->creationDateAndTime = new DateTimeImmutable($xpath->query('xmlns:CreDtTm', $groupHeaderNode)[0]->nodeValue);
        $groupHeader->numberOfTransactions = (int)$xpath->query('xmlns:NbOfTxs', $groupHeaderNode)[0]->nodeValue;

        return $groupHeader;
    }

    private function paymentInformation(DOMDocument $document): PaymentInformation
    {
        $xpath = self::xpath($document);

        $paymentInformationNode = match ($this->version($document)) {
            self::PAIN_008_001_02 => $xpath->query('xmlns:CstmrDrctDbtInitn/xmlns:PmtInf')[0] ?? null,
        };

        if (!$paymentInformationNode) {
            throw new MissingPaymentInformation(sprintf(
                'Missing payment information in "%s"',
                $document->baseURI,
            ));
        }

        $paymentInformation = new PaymentInformation();
        $paymentInformation->id = $xpath->query('xmlns:PmtInfId', $paymentInformationNode)[0]->nodeValue;
        $paymentInformation->sequenceType = $xpath->query('xmlns:PmtTpInf/xmlns:SeqTp', $paymentInformationNode)[0]->nodeValue;
        $paymentInformation->requestedCollectionDate = new DateTimeImmutable($xpath->query('xmlns:ReqdColltnDt', $paymentInformationNode)[0]->nodeValue);
        $paymentInformation->creditorName = $xpath->query('xmlns:Cdtr/xmlns:Nm', $paymentInformationNode)[0]?->nodeValue;
        $paymentInformation->creditorIban = $xpath->query('xmlns:CdtrAcct/xmlns:Id/xmlns:IBAN', $paymentInformationNode)[0]?->nodeValue;
        $paymentInformation->creditorId = $xpath->query('xmlns:CdtrSchmeId//xmlns:PrvtId//xmlns:Id', $paymentInformationNode)[0]?->nodeValue;

        return $paymentInformation;
    }

    private function transfers(DOMDocument $document): array
    {
        $xpath = self::xpath($document);

        /** @var DOMNodeList $transfers */
        $transfers = $xpath->query('xmlns:CstmrDrctDbtInitn/xmlns:PmtInf//xmlns:DrctDbtTxInf');

        return array_map(static function (DOMElement $element) use ($xpath) {
            $transfer = new DirectDebitTransactionInformation();
            $transfer->paymentId = $xpath->query('xmlns:PmtId/xmlns:EndToEndId', $element)[0]->nodeValue;
            $transfer->instructedAmount = Money::of($xpath->query('xmlns:InstdAmt', $element)[0]->nodeValue, 'EUR');
            $transfer->name = $xpath->query('xmlns:Dbtr/xmlns:Nm', $element)[0]->nodeValue;
            $transfer->iban = $xpath->query('xmlns:DbtrAcct/xmlns:Id/xmlns:IBAN', $element)[0]->nodeValue;
            $transfer->remittanceInformation = $xpath->query('xmlns:RmtInf/xmlns:Ustrd', $element)[0]->nodeValue;

            return $transfer;
        }, iterator_to_array($transfers));
    }

    private static function xpath(DOMDocument $document): DOMXPath
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xmlns', $document->documentElement->getAttribute('xmlns'));

        return $xpath;
    }
}
