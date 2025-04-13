<?php

declare(strict_types=1);

namespace Valantic\PimcoreApiDocumentationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Valantic\PimcoreApiDocumentationBundle\DependencyInjection\CompilerPass\ApiControllerCompilerPass;
use Valantic\PimcoreApiDocumentationBundle\DependencyInjection\CompilerPass\DataTypeParserCompilerPass;

class ValanticPimcoreApiDocumentationBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ApiControllerCompilerPass());
        $container->addCompilerPass(new DataTypeParserCompilerPass());
    }
}
