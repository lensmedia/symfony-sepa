<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Exception;

use RuntimeException;

class MissingPaymentInformation extends RuntimeException implements SepaExceptionInterface
{
}
