<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\Exception;

use LogicException;

class UnsupportedVersion extends LogicException implements SepaExceptionInterface
{
}
