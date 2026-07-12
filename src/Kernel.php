<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Old AppKernel::init() did this; MM sync timestamps and Gedmo Timestampable depend
        // on it (see docs/port-inventory/services.md §1).
        date_default_timezone_set('Europe/Athens');

        parent::boot();
    }
}
