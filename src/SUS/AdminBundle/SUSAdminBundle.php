<?php

namespace SUS\AdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use SUS\AdminBundle\DependencyInjection\Compiler\DisableVotersPass;

class SUSAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DisableVotersPass());
    }
}
