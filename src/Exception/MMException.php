<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Thrown when communication with the MM (Μητρώο Μονάδων) API fails.
 * (old `SUS\SiteBundle\Exception\MMException`)
 */
class MMException extends \RuntimeException implements SUSSiteBundleExceptionInterface
{
}
