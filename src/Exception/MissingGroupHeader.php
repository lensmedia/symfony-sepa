<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Exception;

use RuntimeException;

class MissingGroupHeader extends RuntimeException implements SepaExceptionInterface
{
}
