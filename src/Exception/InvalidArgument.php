<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Exception;

use InvalidArgumentException;

class InvalidArgument extends InvalidArgumentException implements SepaExceptionInterface
{
}
