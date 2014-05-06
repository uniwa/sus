<?php

namespace SUS\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SUSUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
